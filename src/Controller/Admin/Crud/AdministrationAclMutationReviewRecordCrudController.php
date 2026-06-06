<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationAclMutationReviewRecord;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationAclMutationReviewRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationAclMutationReviewRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.connected_component.evidence_review.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('requestKey');
        yield TextField::new('mutationType');
        yield TextField::new('subjectIdentifier');
        yield TextField::new('permissionOrRoleKey');
        yield TextField::new('scopeKey');
        yield TextField::new('requestedBySubject');
        yield BooleanField::new('valid');
        yield ArrayField::new('safeReviewPayload')->hideOnIndex();
        yield DateTimeField::new('createdAt')->hideOnForm();
    }
}
