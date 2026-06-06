<?php

declare(strict_types=1);

namespace App\Administering\Builder\Admin;

use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
use App\Administering\Provider\Admin\AdministrationRuntimeSourceNavigationProvider;
use App\Administering\ProviderInterface\Admin\AdministrationServiceToolMenuSectionProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Menu\MenuItemInterface;

/**
 * Builds the left EasyAdmin menu from the materialized service-tool index.
 *
 * Section items link to the stock EasyAdmin CRUD index for
 * AdministrationServiceToolRecord with a sectionKey filter. The SQLite
 * projection controls counts/readiness while the static section catalog keeps
 * icons and permissions. If the SQLite schema has not been created yet, the
 * provider falls back to the static filesystem catalog.
 */
final readonly class AdministrationMainMenuBuilder implements AdministrationMainMenuBuilderInterface
{
    public function __construct(
        private AdministrationServiceToolMenuSectionProviderInterface $menuSectionProvider,
        private AdministrationRuntimeSourceNavigationProvider $runtimeSourceNavigationProvider,
    ) {
    }

    public function build(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home')
            ->setPermission('administration.dashboard.view');

        yield MenuItem::linkToUrl('Configuration Center', 'fa fa-cogs', '/admin/config')
            ->setPermission('administration.config.view');

        yield MenuItem::subMenu('Runtime Scope', 'fa fa-layer-group')
            ->setPermission('administration.dashboard.view')
            ->setSubItems(array_map(
                static fn ($item): MenuItemInterface => MenuItem::linkToRoute($item->label, $item->icon, $item->routeName)
                    ->setPermission($item->permission),
                $this->runtimeSourceNavigationProvider->items(),
            ));

        yield MenuItem::subMenu('Connected Components', 'fa fa-plug')
            ->setPermission('administration.connected_component.overview.view')
            ->setSubItems([
                MenuItem::linkToRoute('Overview', 'fa fa-diagram-project', 'administration_connected_component_overview'),
                MenuItem::linkToRoute('Readiness', 'fa fa-clipboard-check', 'administration_connected_component_readiness'),
                MenuItem::linkToRoute('Capability matrix', 'fa fa-table-cells', 'administration_connected_component_capability_matrix'),
                MenuItem::linkToRoute('Contract matrix', 'fa fa-code-compare', 'administration_connected_component_contract_matrix'),
                MenuItem::linkToRoute('Health', 'fa fa-heart-pulse', 'administration_connected_component_health'),
                MenuItem::linkToRoute('Diagnostics', 'fa fa-stethoscope', 'administration_connected_component_diagnostics'),
                MenuItem::linkToRoute('Remediation', 'fa fa-screwdriver-wrench', 'administration_connected_component_remediation'),
                MenuItem::linkToRoute('Work plan', 'fa fa-list-check', 'administration_connected_component_work_plan'),
                MenuItem::linkToRoute('Execution plan', 'fa fa-person-running', 'administration_connected_component_execution_plan'),
            ]);

        foreach ($this->menuSectionProvider->menuSections() as $section) {
            $sectionUrl = '/admin/administration-service-tool-record?'.http_build_query([
                'filters' => [
                    'sectionKey' => [
                        'comparison' => '=',
                        'value' => $section->key,
                    ],
                ],
            ]);

            yield MenuItem::linkToUrl(
                $section->menuLabel(),
                $section->icon,
                $sectionUrl,
            )
                ->setPermission($section->permission);
        }
    }
}
