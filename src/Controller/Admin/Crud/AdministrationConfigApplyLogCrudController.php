<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\Config\AdministrationConfigApplyLog;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConfigApplyLogCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConfigApplyLog::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('applicationCode');
        yield TextField::new('toolCode');
        yield TextField::new('actorIdentifier');
        yield TextField::new('status');
        yield ArrayField::new('changedFields')->hideOnIndex();
        yield ArrayField::new('maskedSecrets')->hideOnIndex();
        yield TextField::new('errorMessage')->hideOnIndex();
        yield DateTimeField::new('appliedAt');
    }
}
