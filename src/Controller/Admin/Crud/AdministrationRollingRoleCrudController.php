<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingRole;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

final class AdministrationRollingRoleCrudController extends AbstractAdministrationRollingCrudController
{
    use AdministrationRollingCrudActionSupportTrait;

    public static function getEntityFqcn(): string
    {
        return RollingRole::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No roles synced yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'Run the Rolling catalog sync first. Once roles exist, you can enable, disable, and inspect them here.';
    }

    public function configureActions(Actions $actions): Actions
    {
        $enable = Action::new('enable', 'Enable', 'fa fa-toggle-on')
            ->linkToCrudAction('enable')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf(static fn (RollingRole $role): bool => !$role->isEnabled());

        $disable = Action::new('disable', 'Disable', 'fa fa-toggle-off')
            ->linkToCrudAction('disable')
            ->renderAsForm()
            ->asWarningAction()
            ->displayIf(static fn (RollingRole $role): bool => $role->isEnabled());

        $batchEnable = Action::new('batchEnable', 'Enable selected', 'fa fa-toggle-on')
            ->createAsBatchAction()
            ->linkToCrudAction('batchEnable')
            ->asSuccessAction();

        $batchDisable = Action::new('batchDisable', 'Disable selected', 'fa fa-toggle-off')
            ->createAsBatchAction()
            ->linkToCrudAction('batchDisable')
            ->asWarningAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_DETAIL, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_INDEX, $enable)
            ->add(Crud::PAGE_DETAIL, $enable)
            ->add(Crud::PAGE_INDEX, $disable)
            ->add(Crud::PAGE_DETAIL, $disable)
            ->addBatchAction($batchEnable)
            ->addBatchAction($batchDisable)
            ->setPermission('subjectAccessReport', 'administration.rolling.subject_access_report.view')
            ->setPermission('permissionCatalog', 'administration.rolling.permission_catalog.view')
            ->setPermission('aclMutations', 'administration.rolling.acl_mutation.review.view')
            ->setPermission('aclApply', 'administration.rolling.acl_mutation.apply.view')
            ->setPermission('enable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('disable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchEnable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchDisable', 'administration.rolling.acl_mutation.apply');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('roleKey');
        yield TextField::new('label');
        yield BooleanField::new('systemRole')->hideOnForm();
        yield BooleanField::new('enabled')->hideOnForm();
    }

    #[AdminRoute(path: '/{entityId}/enable', name: 'enable', options: ['methods' => ['GET', 'POST']])]
    public function enable(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingRole $role */
        $role = $this->rollingManagedEntity($context, RollingRole::class);
        $role->setEnabled(true);

        return $this->rollingPersistAndRedirect($context, $role, sprintf('Role "%s" enabled.', $role->getRoleKey()));
    }

    #[AdminRoute(path: '/{entityId}/disable', name: 'disable', options: ['methods' => ['GET', 'POST']])]
    public function disable(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingRole $role */
        $role = $this->rollingManagedEntity($context, RollingRole::class);
        $role->setEnabled(false);

        return $this->rollingPersistAndRedirect($context, $role, sprintf('Role "%s" disabled.', $role->getRoleKey()));
    }

    #[AdminRoute(path: '/batch/enable', name: 'batch_enable', options: ['methods' => ['POST']])]
    public function batchEnable(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingRole::class,
            static function (RollingRole $role): void {
                $role->setEnabled(true);
            },
            '%d selected roles enabled.',
        );
    }

    #[AdminRoute(path: '/batch/disable', name: 'batch_disable', options: ['methods' => ['POST']])]
    public function batchDisable(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingRole::class,
            static function (RollingRole $role): void {
                $role->setEnabled(false);
            },
            '%d selected roles disabled.',
        );
    }
}
