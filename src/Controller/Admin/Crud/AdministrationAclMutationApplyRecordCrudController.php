<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationAclMutationApplyRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationAclMutationApplyRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.acl_mutation.apply.view';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('ACL Mutation Apply Record')
            ->setEntityLabelInPlural('ACL Mutation Apply Records')
            ->setDefaultSort(['id' => 'DESC']);
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
        yield TextField::new('status');
        yield BooleanField::new('succeeded');
        yield TextField::new('safeMessage');
        yield ArrayField::new('safeResultPayload')->hideOnIndex();
        yield DateTimeField::new('createdAt')->hideOnForm();
    }
}
