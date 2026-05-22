<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationOperationRun;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationOperationRunCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationOperationRun::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.operation.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('operationKey');
        yield TextField::new('operationType');
        yield TextField::new('status');
        yield TextField::new('subjectIdentifier');
        yield TextField::new('targetReference')->hideOnForm();
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('createdAt');
        yield DateTimeField::new('startedAt')->hideOnForm();
        yield DateTimeField::new('finishedAt')->hideOnForm();
    }
}
