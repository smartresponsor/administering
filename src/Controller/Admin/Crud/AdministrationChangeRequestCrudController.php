<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationChangeRequest;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationChangeRequestCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationChangeRequest::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('requestKey');
        yield TextField::new('changeType');
        yield TextField::new('targetReference');
        yield TextField::new('status');
        yield ArrayField::new('payload')->hideOnIndex();
        yield DateTimeField::new('createdAt');
    }
}
