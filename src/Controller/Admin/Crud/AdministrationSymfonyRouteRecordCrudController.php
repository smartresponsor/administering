<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationSymfonyRouteRecordEntity;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationSymfonyRouteRecordCrudController extends AdministrationAbstractReadOnlyCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationSymfonyRouteRecordEntity::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('routeName');
        yield TextField::new('path');
        yield ArrayField::new('methods')->hideOnIndex();
        yield TextField::new('controller')->hideOnIndex();
        yield IntegerField::new('statusCode');
        yield TextField::new('statusClass');
        yield DateTimeField::new('checkedAt');
    }
}
