<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationCredentialState;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationCredentialStateCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationCredentialState::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('credentialKey');
        yield TextField::new('environmentName');
        yield BooleanField::new('present');
        yield TextField::new('sourceType');
        yield TextField::new('safeFingerprint')->hideOnIndex();
        yield TextField::new('status');
        yield DateTimeField::new('checkedAt');
    }
}
