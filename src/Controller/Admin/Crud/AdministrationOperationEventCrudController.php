<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationOperationEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationOperationEventCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationOperationEvent::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.operation.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('operationKey');
        yield TextField::new('status');
        yield TextField::new('safeMessage');
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('createdAt')->hideOnForm();
    }
}
