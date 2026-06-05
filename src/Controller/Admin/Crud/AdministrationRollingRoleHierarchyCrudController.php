<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingRoleHierarchy;
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

/**
 * Read-only Administering surface for Rolling role inheritance edges.
 *
 * Direct hierarchy mutations must go through Rolling-owned reviewed tools rather
 * than direct EasyAdmin entity edits. This screen closes the visibility gap for
 * the default administration hierarchy and any synchronized role edge state.
 */
final class AdministrationRollingRoleHierarchyCrudController extends AbstractAdministrationRollingCrudController
{
    use AdministrationRollingCrudActionSupportTrait;

    public static function getEntityFqcn(): string
    {
        return RollingRoleHierarchy::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No hierarchy edges yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'This screen stays empty until role inheritance edges are synced. Use ACL mutation tools to review or apply hierarchy changes.';
    }

    public function configureActions(Actions $actions): Actions
    {
        $enable = Action::new('enable', 'Enable', 'fa fa-toggle-on')
            ->linkToCrudAction('enable')
            ->renderAsForm()
            ->asSuccessAction()
            ->displayIf(static fn (RollingRoleHierarchy $hierarchy): bool => !$hierarchy->isEnabled());

        $disable = Action::new('disable', 'Disable', 'fa fa-toggle-off')
            ->linkToCrudAction('disable')
            ->renderAsForm()
            ->asWarningAction()
            ->displayIf(static fn (RollingRoleHierarchy $hierarchy): bool => $hierarchy->isEnabled());

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
        yield TextField::new('parentRoleKey', 'Parent role');
        yield TextField::new('childRoleKey', 'Child role');
        yield BooleanField::new('enabled')->hideOnForm();
    }

    #[AdminRoute(path: '/{entityId}/enable', name: 'enable', options: ['methods' => ['GET', 'POST']])]
    public function enable(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingRoleHierarchy $hierarchy */
        $hierarchy = $this->rollingManagedEntity($context, RollingRoleHierarchy::class);
        $hierarchy->enable();

        return $this->rollingPersistAndRedirect($context, $hierarchy, sprintf('Hierarchy edge "%s" -> "%s" enabled.', $hierarchy->parentRoleKey(), $hierarchy->childRoleKey()));
    }

    #[AdminRoute(path: '/{entityId}/disable', name: 'disable', options: ['methods' => ['GET', 'POST']])]
    public function disable(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingRoleHierarchy $hierarchy */
        $hierarchy = $this->rollingManagedEntity($context, RollingRoleHierarchy::class);
        $hierarchy->disable();

        return $this->rollingPersistAndRedirect($context, $hierarchy, sprintf('Hierarchy edge "%s" -> "%s" disabled.', $hierarchy->parentRoleKey(), $hierarchy->childRoleKey()));
    }

    #[AdminRoute(path: '/batch/enable', name: 'batch_enable', options: ['methods' => ['POST']])]
    public function batchEnable(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingRoleHierarchy::class,
            static function (RollingRoleHierarchy $hierarchy): void {
                $hierarchy->enable();
            },
            '%d selected hierarchy edges enabled.',
        );
    }

    #[AdminRoute(path: '/batch/disable', name: 'batch_disable', options: ['methods' => ['POST']])]
    public function batchDisable(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchMutate(
            $context,
            $batchActionDto,
            RollingRoleHierarchy::class,
            static function (RollingRoleHierarchy $hierarchy): void {
                $hierarchy->disable();
            },
            '%d selected hierarchy edges disabled.',
        );
    }
}
