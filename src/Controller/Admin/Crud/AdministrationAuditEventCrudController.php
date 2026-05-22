<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationAuditEvent;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationAuditEventCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationAuditEvent::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.accessing.account_action.audit.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('action');
        yield TextField::new('subjectIdentifier');
        yield ArrayField::new('context')->hideOnIndex();
        yield DateTimeField::new('createdAt');
    }
}
