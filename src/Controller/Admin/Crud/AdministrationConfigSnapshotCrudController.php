<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationConfigSnapshot;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConfigSnapshotCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConfigSnapshot::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('sourceType');
        yield TextField::new('sourcePath');
        yield TextField::new('componentName')->hideOnForm();
        yield TextField::new('checksum')->hideOnIndex();
        yield ArrayField::new('normalizedEntries')->hideOnIndex();
        yield DateTimeField::new('scannedAt');
    }
}
