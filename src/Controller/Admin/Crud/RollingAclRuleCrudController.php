<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingAclRule;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

final class RollingAclRuleCrudController extends AbstractRollingAdministrationCrudController
{
    use RollingCrudActionSupportTrait;

    public static function getEntityFqcn(): string
    {
        return RollingAclRule::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No direct ACL rules yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'Direct rules are usually created through the Rolling mutation surfaces. Add rules there, then return here for row-level and bulk control.';
    }

    public function configureActions(Actions $actions): Actions
    {
        $enable = Action::new('enable', 'Enable', 'fa fa-toggle-on')
            ->linkToCrudAction('enable')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf(static fn (RollingAclRule $rule): bool => !$rule->isEnabled());

        $disable = Action::new('disable', 'Disable', 'fa fa-toggle-off')
            ->linkToCrudAction('disable')
            ->renderAsForm()
            ->asWarningAction()
            ->displayIf(static fn (RollingAclRule $rule): bool => $rule->isEnabled());

        $allow = Action::new('allow', 'Allow', 'fa fa-check')
            ->linkToCrudAction('allow')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf(static fn (RollingAclRule $rule): bool => 'allow' !== $rule->getEffect());

        $deny = Action::new('deny', 'Deny', 'fa fa-ban')
            ->linkToCrudAction('deny')
            ->renderAsForm()
            ->asDangerAction()
            ->displayIf(static fn (RollingAclRule $rule): bool => 'deny' !== $rule->getEffect());

        $batchEnable = Action::new('batchEnable', 'Enable selected', 'fa fa-toggle-on')
            ->createAsBatchAction()
            ->linkToCrudAction('batchEnable')
            ->asSuccessAction();

        $batchDisable = Action::new('batchDisable', 'Disable selected', 'fa fa-toggle-off')
            ->createAsBatchAction()
            ->linkToCrudAction('batchDisable')
            ->asWarningAction();

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
            ->add(Crud::PAGE_INDEX, $enable)
            ->add(Crud::PAGE_DETAIL, $enable)
            ->add(Crud::PAGE_INDEX, $disable)
            ->add(Crud::PAGE_DETAIL, $disable)
            ->add(Crud::PAGE_INDEX, $allow)
            ->add(Crud::PAGE_DETAIL, $allow)
            ->add(Crud::PAGE_INDEX, $deny)
            ->add(Crud::PAGE_DETAIL, $deny)
            ->addBatchAction($batchEnable)
            ->addBatchAction($batchDisable)
            ->addBatchAction($batchAllow)
            ->addBatchAction($batchDeny)
            ->setPermission('subjectAccessReport', 'administration.rolling.subject_access_report.view')
            ->setPermission('permissionCatalog', 'administration.rolling.permission_catalog.view')
            ->setPermission('aclMutations', 'administration.rolling.acl_mutation.review.view')
            ->setPermission('aclApply', 'administration.rolling.acl_mutation.apply.view')
            ->setPermission('enable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('disable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('allow', 'administration.rolling.acl_mutation.apply')
            ->setPermission('deny', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchEnable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchDisable', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchAllow', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchDeny', 'administration.rolling.acl_mutation.apply');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('subjectIdentifier');
        yield TextField::new('permissionKey');
        yield TextField::new('scopeKey');
        yield ChoiceField::new('effect')->setChoices([
            'Allow' => 'allow',
            'Deny' => 'deny',
        ]);
        yield BooleanField::new('enabled');
        yield ArrayField::new('conditions')->hideOnIndex();
    }

    #[AdminRoute(path: '/{entityId}/enable', name: 'enable', options: ['methods' => ['GET', 'POST']])]
    public function enable(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingAclRule $rule */
        $rule = $this->rollingManagedEntity($context, RollingAclRule::class);
        $rule->setEnabled(true);

        return $this->rollingPersistAndRedirect($context, $rule, sprintf('ACL rule "%s / %s" enabled.', $rule->getSubjectIdentifier(), $rule->getPermissionKey()));
    }

    #[AdminRoute(path: '/{entityId}/disable', name: 'disable', options: ['methods' => ['GET', 'POST']])]
    public function disable(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingAclRule $rule */
        $rule = $this->rollingManagedEntity($context, RollingAclRule::class);
        $rule->setEnabled(false);

        return $this->rollingPersistAndRedirect($context, $rule, sprintf('ACL rule "%s / %s" disabled.', $rule->getSubjectIdentifier(), $rule->getPermissionKey()));
    }

    #[AdminRoute(path: '/{entityId}/allow', name: 'allow', options: ['methods' => ['GET', 'POST']])]
    public function allow(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingAclRule $rule */
        $rule = $this->rollingManagedEntity($context, RollingAclRule::class);
        $rule->setEffect('allow');

        return $this->rollingPersistAndRedirect($context, $rule, sprintf('ACL rule "%s / %s" set to allow.', $rule->getSubjectIdentifier(), $rule->getPermissionKey()));
    }

    #[AdminRoute(path: '/{entityId}/deny', name: 'deny', options: ['methods' => ['GET', 'POST']])]
    public function deny(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingAclRule $rule */
        $rule = $this->rollingManagedEntity($context, RollingAclRule::class);
        $rule->setEffect('deny');

        return $this->rollingPersistAndRedirect($context, $rule, sprintf('ACL rule "%s / %s" set to deny.', $rule->getSubjectIdentifier(), $rule->getPermissionKey()));
    }

    #[AdminRoute(path: '/batch/enable', name: 'batch_enable', options: ['methods' => ['POST']])]
    public function batchEnable(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingAclRule::class,
            static function (RollingAclRule $rule): void {
                $rule->setEnabled(true);
            },
            '%d selected ACL rules enabled.',
        );
    }

    #[AdminRoute(path: '/batch/disable', name: 'batch_disable', options: ['methods' => ['POST']])]
    public function batchDisable(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingAclRule::class,
            static function (RollingAclRule $rule): void {
                $rule->setEnabled(false);
            },
            '%d selected ACL rules disabled.',
        );
    }

    #[AdminRoute(path: '/batch/allow', name: 'batch_allow', options: ['methods' => ['POST']])]
    public function batchAllow(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingAclRule::class,
            static function (RollingAclRule $rule): void {
                $rule->setEffect('allow');
            },
            '%d selected ACL rules set to allow.',
        );
    }

    #[AdminRoute(path: '/batch/deny', name: 'batch_deny', options: ['methods' => ['POST']])]
    public function batchDeny(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingAclRule::class,
            static function (RollingAclRule $rule): void {
                $rule->setEffect('deny');
            },
            '%d selected ACL rules set to deny.',
        );
    }
}
