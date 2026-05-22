<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingRole;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RollingRoleCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return RollingRole::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('roleKey');
        yield TextField::new('label');
        yield BooleanField::new('systemRole')->hideOnForm();
        yield BooleanField::new('enabled')->hideOnForm();
    }
}
