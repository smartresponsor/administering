<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Rolling\Entity\Acl\RollingAclRule;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class RollingAclRuleCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return RollingAclRule::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.rolling.subject_access_report.view';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('subjectIdentifier');
        yield TextField::new('permissionKey');
        yield TextField::new('scopeKey');
        yield ChoiceField::new('effect')->setChoices([
            'Allow' => 'allow',
            'Deny' => 'deny',
        ]);
        yield BooleanField::new('enabled');
        yield ArrayField::new('conditions')->hideOnIndex();
    }
}
