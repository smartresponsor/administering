<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationServiceToolRecord;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationServiceToolRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationServiceToolRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.dashboard.view';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Administration service tool')
            ->setEntityLabelInPlural('Administration service tools')
            ->setDefaultSort(['sectionKey' => 'ASC', 'position' => 'ASC', 'toolSlug' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $openTool = Action::new('openTool', 'Open tool')
            ->linkToRoute('administration_service_tool_open', static fn (AdministrationServiceToolRecord $record): array => [
                'toolKey' => $record->getToolKey(),
            ])
            ->displayIf(static fn (AdministrationServiceToolRecord $record): bool => $record->isOpenable());

        $runtimeControls = Action::new('runtimeControls', 'Runtime controls')
            ->linkToRoute('administration_service_tool_runtime_controls', static fn (AdministrationServiceToolRecord $record): array => [
                'toolKey' => $record->getToolKey(),
            ]);

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $openTool)
            ->add(Crud::PAGE_DETAIL, $openTool)
            ->add(Crud::PAGE_INDEX, $runtimeControls)
            ->add(Crud::PAGE_DETAIL, $runtimeControls);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('sectionKey')
            ->add('toolKey')
            ->add('status')
            ->add('sourceOwnership')
            ->add('ownerComponentKey')
            ->add('executable')
            ->add('enabled')
            ->add('visible');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('sectionKey');
        yield TextField::new('toolKey');
        yield TextField::new('displayLabel', 'Display label');
        yield TextField::new('label', 'Generated label')->hideOnIndex();
        yield TextField::new('labelOverride')->hideOnIndex();
        yield TextField::new('toolSlug')->hideOnIndex();
        yield TextField::new('sourceOwnership', 'Source');
        yield TextField::new('sourceLabel', 'Source label')->hideOnIndex();
        yield TextField::new('ownerComponentKey')->hideOnIndex();
        yield TextField::new('ownerComponentToken')->hideOnIndex();
        yield TextField::new('ownerProviderClass')->hideOnIndex();
        yield TextField::new('ownerServiceClass')->hideOnIndex();
        yield TextField::new('ownerSourceLabel')->hideOnIndex();
        yield TextField::new('serviceShortName')->hideOnIndex();
        yield TextField::new('serviceClass')->hideOnIndex();
        yield TextField::new('serviceFile')->hideOnIndex();
        yield TextField::new('formTypeClass')->hideOnIndex();
        yield TextField::new('formDataClass')->hideOnIndex();
        yield BooleanField::new('hasFormType', 'Form mapped');
        yield BooleanField::new('hasFormDataClass', 'Data mapped')->hideOnIndex();
        yield TextField::new('operationType');
        yield BooleanField::new('executable');
        yield BooleanField::new('runnable', 'Runnable');
        yield TextField::new('primaryRouteName')->hideOnIndex();
        yield TextField::new('status');
        yield BooleanField::new('enabled');
        yield BooleanField::new('visible');
        yield IntegerField::new('position')->hideOnIndex();
        yield TextField::new('checksum')->hideOnIndex();
        yield ArrayField::new('safeContext')->hideOnIndex();
        yield DateTimeField::new('synchronizedAt');
    }
}
