<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationAccountActionRequestRecord;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationAccountActionRequestRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationAccountActionRequestRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.audit.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('requestKey');
        yield TextField::new('action');
        yield TextField::new('accountReference');
        yield TextField::new('requestedBySubject');
        yield TextField::new('status');
        yield TextareaField::new('safeReason')->hideOnIndex();
        yield TextareaField::new('safeResultMessage')->hideOnIndex();
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('createdAt')->hideOnForm();
    }
}
