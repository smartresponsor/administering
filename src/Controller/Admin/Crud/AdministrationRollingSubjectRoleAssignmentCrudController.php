<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingSubjectRoleAssignment;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

final class AdministrationRollingSubjectRoleAssignmentCrudController extends AbstractAdministrationRollingCrudController
{
    use AdministrationRollingCrudActionSupportTrait;

    public static function getEntityFqcn(): string
    {
        return RollingSubjectRoleAssignment::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No subject assignments yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'Subject role assignments appear after access setup. Use the revoke action here to clean up stale assignments.';
    }

    public function configureActions(Actions $actions): Actions
    {
        $revoke = Action::new('revoke', 'Revoke', 'fa fa-trash')
            ->linkToCrudAction('revoke')
            ->renderAsForm()
            ->asDangerAction();

        $batchRevoke = Action::new('batchRevoke', 'Revoke selected', 'fa fa-trash')
            ->createAsBatchAction()
            ->linkToCrudAction('batchRevoke')
            ->asDangerAction();

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_DETAIL, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_INDEX, $revoke)
            ->add(Crud::PAGE_DETAIL, $revoke)
            ->addBatchAction($batchRevoke)
            ->setPermission('subjectAccessReport', 'administration.rolling.subject_access_report.view')
            ->setPermission('permissionCatalog', 'administration.rolling.permission_catalog.view')
            ->setPermission('aclMutations', 'administration.rolling.acl_mutation.review.view')
            ->setPermission('aclApply', 'administration.rolling.acl_mutation.apply.view')
            ->setPermission('revoke', 'administration.rolling.acl_mutation.apply')
            ->setPermission('batchRevoke', 'administration.rolling.acl_mutation.apply');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('subjectIdentifier');
        yield TextField::new('roleKey');
        yield TextField::new('scopeKey');
        yield DateTimeField::new('assignedAt')->hideOnForm();
    }

    #[AdminRoute(path: '/{entityId}/revoke', name: 'revoke', options: ['methods' => ['GET', 'POST']])]
    public function revoke(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        /** @var RollingSubjectRoleAssignment $assignment */
        $assignment = $this->rollingManagedEntity($context, RollingSubjectRoleAssignment::class);

        return $this->rollingRemoveAndRedirect(
            $context,
            $assignment,
            sprintf('Role assignment "%s / %s" revoked.', $assignment->getSubjectIdentifier(), $assignment->getRoleKey())
        );
    }

    #[AdminRoute(path: '/batch/revoke', name: 'batch_revoke', options: ['methods' => ['POST']])]
    public function batchRevoke(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->denyAccessUnlessGranted('administration.rolling.acl_mutation.apply', 'administering:rolling');

        return $this->rollingBatchRemove(
            $context,
            $batchActionDto,
            RollingSubjectRoleAssignment::class,
            '%d selected role assignments revoked.',
        );
    }
}
