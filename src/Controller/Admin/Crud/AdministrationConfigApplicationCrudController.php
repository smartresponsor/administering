<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\Config\AdministrationConfigApplication;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConfigApplicationCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConfigApplication::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('applicationCode');
        yield TextField::new('label');
        yield TextField::new('rootPath')->hideOnIndex();
        yield TextField::new('manifestPath')->hideOnIndex();
        yield TextField::new('status');
        yield BooleanField::new('enabled');
        yield TextField::new('checksum')->hideOnIndex();
        yield DateTimeField::new('discoveredAt');
    }
}
