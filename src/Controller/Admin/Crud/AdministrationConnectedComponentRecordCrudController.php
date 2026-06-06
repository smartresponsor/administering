<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use App\Administering\Entity\AdministrationConnectedComponentRecord;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AdministrationConnectedComponentRecordCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdministrationConnectedComponentRecord::class;
    }

    protected function entityPermission(): string
    {
        return 'administration.connected_component.overview.view';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Enabled Component')
            ->setEntityLabelInPlural('Enabled Components')
            ->setDefaultSort(['componentName' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $changeDecision = Action::new('changeRuntimeScopeDecision', 'Change decision')
            ->linkToRoute('administration_runtime_scope_component_decision', static fn (AdministrationConnectedComponentRecord $record): array => [
                'componentKey' => $record->getComponentName(),
            ]);

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $changeDecision)
            ->add(Crud::PAGE_DETAIL, $changeDecision);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('componentName')
            ->add('status')
            ->add('readinessStatus');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('componentName', 'Component');
        yield BooleanField::new('installed', 'Installed');
        yield BooleanField::new('enabledForDev', 'Dev');
        yield BooleanField::new('enabledForProd', 'Prod');
        yield BooleanField::new('enabledInCurrentScope', 'Enabled now');
        yield TextField::new('devDecision', 'Dev decision')->hideOnIndex();
        yield TextField::new('prodDecision', 'Prod decision')->hideOnIndex();
        yield TextField::new('status', 'Current decision');
        yield TextField::new('runtimeScope', 'Runtime scope');
        yield BooleanField::new('inRuntimeScope', 'In APP_RUNTIME_SCOPE')->hideOnIndex();
        yield BooleanField::new('disabledByRuntimeLock', 'Disabled by lock')->hideOnIndex();
        yield TextField::new('decisionReason', 'Reason')->hideOnIndex();
        yield TextField::new('composerPackage', 'Composer package')->hideOnIndex();
        yield TextField::new('bundleToken', 'Bundle token')->hideOnIndex();
        yield TextField::new('readinessStatus', 'Readiness')->hideOnIndex();
        yield ArrayField::new('safeSummary', 'Evidence')->hideOnIndex();
        yield DateTimeField::new('synchronizedAt', 'Updated');
    }
}
