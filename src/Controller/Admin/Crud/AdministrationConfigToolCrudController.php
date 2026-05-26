<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\Config\AdministrationConfigTool;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConfigToolCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConfigTool::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.config.view';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Configuration tool')
            ->setEntityLabelInPlural('Configuration tools');
    }

    public function configureActions(Actions $actions): Actions
    {
        $editConfig = Action::new('editConfig', 'Edit config')
            ->linkToRoute('administration_config_tool_edit', static fn (AdministrationConfigTool $tool): array => [
                'applicationCode' => $tool->getApplicationCode(),
                'toolCode' => $tool->getToolCode(),
            ]);

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $editConfig)
            ->add(Crud::PAGE_DETAIL, $editConfig);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('applicationCode');
        yield TextField::new('toolCode');
        yield TextField::new('label');
        yield TextField::new('description')->hideOnIndex();
        yield TextField::new('formClass')->hideOnIndex();
        yield TextField::new('serviceClass')->hideOnIndex();
        yield TextField::new('requiredPermission');
        yield TextField::new('applyStrategy');
        yield TextField::new('status');
        yield ArrayField::new('editableFields')->hideOnIndex();
        yield ArrayField::new('sensitiveFields')->hideOnIndex();
        yield ArrayField::new('readableFiles')->hideOnIndex();
        yield ArrayField::new('writableFiles')->hideOnIndex();
        yield ArrayField::new('secretNames')->hideOnIndex();
        yield ArrayField::new('metadata')->hideOnIndex();
        yield DateTimeField::new('discoveredAt');
    }
}
