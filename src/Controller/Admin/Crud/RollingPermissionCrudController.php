<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingPermission;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RollingPermissionCrudController extends AbstractRollingAdministrationCrudController
{
    use RollingCrudActionSupportTrait;

    public static function getEntityFqcn(): string
    {
        return RollingPermission::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.permission_catalog.view';
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No permissions catalogued yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'The permission catalog is populated by the Rolling sync flow. Once loaded, this screen becomes the canonical permission index.';
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $this->rollingNavigationGroup())
            ->add(Crud::PAGE_DETAIL, $this->rollingNavigationGroup())
            ->setPermission('subjectAccessReport', 'administration.rolling.subject_access_report.view')
            ->setPermission('permissionCatalog', 'administration.rolling.permission_catalog.view')
            ->setPermission('aclMutations', 'administration.rolling.acl_mutation.review.view')
            ->setPermission('aclApply', 'administration.rolling.acl_mutation.apply.view');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('permissionKey');
        yield TextField::new('componentName');
        yield TextareaField::new('description')->hideOnIndex();
    }
}
