<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\Rolling\RollingRolePermission;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

final class AdministrationRollingRolePermissionCrudController extends AbstractAdministrationRollingCrudController
{
    use AdministrationRollingCrudActionSupportTrait;

    public static function getEntityFqcn(): string
    {
        return RollingRolePermission::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No role permissions yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'Role permissions are created during catalog sync and ACL review. Use the bulk actions to align grants with the current policy.';
    }

    public function configureActions(Actions $actions): Actions
    {
        $allow = Action::new('allow', 'Allow', 'fa fa-check')
            ->linkToCrudAction('allow')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf(static fn (RollingRolePermission $grant): bool => 'allow' !== $grant->getEffect());

        $deny = Action::new('deny', 'Deny', 'fa fa-ban')
            ->linkToCrudAction('deny')
            ->renderAsForm()
            ->asDangerAction()
            ->displayIf(static fn (RollingRolePermission $grant): bool => 'deny' !== $grant->getEffect());

        $batchAllow = Action::new('batchAllow', 'Allow selected', 'fa fa-check')
            ->createAsBatchAction()
            ->linkToCrudAction('batchAllow')
            ->asSuccessAction();

        $batchDeny = Action::new('batchDeny', 'Deny selected', 'fa fa-ban')
            ->createAsBatchAction()
            ->linkToCrudAction('batchDeny')
            ->asDangerAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_DETAIL, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_INDEX, $allow)
            ->add(Crud::PAGE_DETAIL, $allow)
            ->add(Crud::PAGE_INDEX, $deny)
            ->add(Crud::PAGE_DETAIL, $deny)
            ->addBatchAction($batchAllow)
            ->addBatchAction($batchDeny)
            ->setPermission('subjectAccessReport', 'administration.rolling.subject_access_report.view')
            ->setPermission('permissionCatalog', 'administration.rolling.permission_catalog.view')
            ->setPermission('aclMutations', 'administration.rolling.acl_mutation.review.view')
            ->setPermission('aclApply', 'administration.rolling.acl_mutation.apply.view')
            ->setPermission('allow', 'administration.rolling.acl_mutation.apply')
            ->setPermission('deny', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchAllow', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchDeny', 'administration.rolling.acl_mutation.apply');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('roleKey');
        yield TextField::new('permissionKey');
        yield TextField::new('scopePattern');
        yield ChoiceField::new('effect')->setChoices([
            'Allow' => 'allow',
            'Deny' => 'deny',
        ]);
    }

    /** @param AdminContext<RollingRolePermission> $context */
    #[AdminRoute(path: '/{entityId}/allow', name: 'allow', options: ['methods' => ['GET', 'POST']])]
    public function allow(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingRolePermission $grant */
        $grant = $this->rollingManagedEntity($context, RollingRolePermission::class);
        $grant->setEffect('allow');

        return $this->rollingPersistAndRedirect($context, $grant, sprintf('Role permission "%s / %s" set to allow.', $grant->getRoleKey(), $grant->getPermissionKey()));
    }

    /** @param AdminContext<RollingRolePermission> $context */
    #[AdminRoute(path: '/{entityId}/deny', name: 'deny', options: ['methods' => ['GET', 'POST']])]
    public function deny(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingRolePermission $grant */
        $grant = $this->rollingManagedEntity($context, RollingRolePermission::class);
        $grant->setEffect('deny');

        return $this->rollingPersistAndRedirect($context, $grant, sprintf('Role permission "%s / %s" set to deny.', $grant->getRoleKey(), $grant->getPermissionKey()));
    }

    /**
     * @param AdminContext<RollingRolePermission>   $context
     * @param BatchActionDto<RollingRolePermission> $batchActionDto
     */
    #[AdminRoute(path: '/batch/allow', name: 'batch_allow', options: ['methods' => ['POST']])]
    public function batchAllow(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingRolePermission::class,
            static function (RollingRolePermission $grant): void {
                $grant->setEffect('allow');
            },
            '%d selected role permissions set to allow.',
        );
    }

    /**
     * @param AdminContext<RollingRolePermission>   $context
     * @param BatchActionDto<RollingRolePermission> $batchActionDto
     */
    #[AdminRoute(path: '/batch/deny', name: 'batch_deny', options: ['methods' => ['POST']])]
    public function batchDeny(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingRolePermission::class,
            static function (RollingRolePermission $grant): void {
                $grant->setEffect('deny');
            },
            '%d selected role permissions set to deny.',
        );
    }
}
