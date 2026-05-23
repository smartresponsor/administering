<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationAccessingAccountRecord;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationAccessingAccountRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationAccessingAccountRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.accessing.account_action.audit.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('accountReference');
        yield TextField::new('displayLabel');
        yield TextField::new('status');
        yield TextField::new('provider');
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('synchronizedAt');
    }
}
