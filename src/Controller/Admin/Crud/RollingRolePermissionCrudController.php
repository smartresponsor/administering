<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingRolePermission;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RollingRolePermissionCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return RollingRolePermission::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
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
}
