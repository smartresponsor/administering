<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationConnectedComponentRecord;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConnectedComponentRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConnectedComponentRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.connected_component.overview.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('componentName');
        yield TextField::new('status');
        yield TextField::new('readinessStatus');
        yield ArrayField::new('safeSummary')->hideOnIndex();
        yield DateTimeField::new('synchronizedAt');
    }
}
