<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationServiceSectionRecord;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationServiceSectionRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationServiceSectionRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.dashboard.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('sectionKey');
        yield TextField::new('label');
        yield TextField::new('serviceDirectory')->hideOnIndex();
        yield TextField::new('status');
        yield IntegerField::new('toolCount');
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('synchronizedAt');
    }
}
