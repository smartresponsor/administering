<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingSubjectRoleAssignment;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RollingSubjectRoleAssignmentCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return RollingSubjectRoleAssignment::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('subjectIdentifier');
        yield TextField::new('roleKey');
        yield TextField::new('scopeKey');
        yield DateTimeField::new('assignedAt')->hideOnForm();
    }
}
