<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationManagingFieldControlRecord;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationManagingFieldControlRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationManagingFieldControlRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.permission_catalog.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('resourceClass');
        yield TextField::new('fieldName');
        yield TextField::new('pageName');
        yield TextField::new('subjectScope');
        yield TextField::new('accessStatus');
        yield TextField::new('visibilityStatus');
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('checkedAt');
    }
}
