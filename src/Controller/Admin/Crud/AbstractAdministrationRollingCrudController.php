<?php

declare(strict_types=1);

namespace App\Administering\Controller\Admin\Crud;

use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;

abstract class AbstractAdministrationRollingCrudController extends AbstractReadOnlyAdministrationCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->showEntityActionsInlined(false)
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $entities = $responseParameters->get('entities');
        if (Crud::PAGE_INDEX === $responseParameters->get('pageName') && $entities instanceof \Countable && 0 === count($entities)) {
            $responseParameters->setIfNotSet('empty_state_title', $this->rollingEmptyStateTitle());
            $responseParameters->setIfNotSet('empty_state_message', $this->rollingEmptyStateMessage());
            $responseParameters->setIfNotSet('empty_state_links', $this->rollingEmptyStateLinks());
        }

        return $responseParameters;
    }

    protected function rollingEmptyStateTitle(): string
    {
        return 'No Rolling records yet';
    }

    protected function rollingEmptyStateMessage(): string
    {
        return 'This list is empty. Use the Rolling panel actions above to inspect the catalog, review access, or open mutation surfaces.';
    }

    /**
     * @return array<int, array{label: string, url: string, variant?: string, icon?: string}>
     */
    protected function rollingEmptyStateLinks(): array
    {
        return [
            [
                'label' => 'Subject access',
                'url' => $this->generateUrl('administration_rolling_subject_access_report'),
                'variant' => 'primary',
                'icon' => 'fa fa-user-shield',
            ],
            [
                'label' => 'Permission catalog',
                'url' => $this->generateUrl('administration_rolling_permission_catalog'),
                'variant' => 'secondary',
                'icon' => 'fa fa-book',
            ],
            [
                'label' => 'ACL mutations',
                'url' => $this->generateUrl('administration_rolling_acl_mutations'),
                'variant' => 'secondary',
                'icon' => 'fa fa-flask',
            ],
        ];
    }

    protected function rollingNavigationGroup(bool $includeAclApply = true): ActionGroup
    {
        $group = ActionGroup::new('rollingTools', 'Rolling tools', 'fa fa-shield-alt')
            ->createAsGlobalActionGroup()
            ->addMainAction(
                Action::new('subjectAccessReport', 'Subject access', 'fa fa-user-shield')
                    ->createAsGlobalAction()
                    ->linkToRoute('administration_rolling_subject_access_report')
                    ->asPrimaryAction()
            )
            ->addAction(
                Action::new('permissionCatalog', 'Permission catalog', 'fa fa-book')
                    ->createAsGlobalAction()
                    ->linkToRoute('administration_rolling_permission_catalog')
            )
            ->addAction(
                Action::new('aclMutations', 'ACL mutations', 'fa fa-flask')
                    ->createAsGlobalAction()
                    ->linkToRoute('administration_rolling_acl_mutations')
            );

        if ($includeAclApply) {
            $group->addAction(
                Action::new('aclApply', 'ACL apply', 'fa fa-play-circle')
                    ->createAsGlobalAction()
                    ->linkToRoute('administration_rolling_acl_mutation_apply')
            );
        }

        return $group->asPrimaryActionGroup();
    }
}
