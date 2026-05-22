<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingPermission;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RollingPermissionCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return RollingPermission::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.permission_catalog.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('permissionKey');
        yield TextField::new('componentName');
        yield TextareaField::new('description')->hideOnIndex();
    }
}
