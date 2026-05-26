<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\Config\AdministrationConfigValue;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConfigValueCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConfigValue::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('applicationCode');
        yield TextField::new('toolCode');
        yield TextField::new('fieldKey');
        yield TextField::new('fieldType');
        yield BooleanField::new('secret');
        yield TextField::new('currentValue')->hideOnIndex();
        yield TextField::new('pendingValue')->hideOnIndex();
        yield TextField::new('maskedValue');
        yield TextField::new('status');
        yield DateTimeField::new('updatedAt');
    }
}
