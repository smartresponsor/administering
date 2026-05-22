<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationCredentialDefinition;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationCredentialDefinitionCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationCredentialDefinition::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('componentName');
        yield TextField::new('credentialKey');
        yield TextField::new('environmentName');
        yield TextField::new('sourceType');
        yield BooleanField::new('required');
        yield TextareaField::new('description')->hideOnIndex();
    }
}
