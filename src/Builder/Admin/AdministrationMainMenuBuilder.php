<?php

declare(strict_types=1);

namespace App\Administering\Builder\Admin;

use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
use App\Administering\Controller\Admin\Crud\AdministrationRollingAclRuleCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationRollingPermissionCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationRollingRoleCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationRollingRoleHierarchyCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationRollingRolePermissionCrudController;
use App\Administering\Controller\Admin\Crud\AdministrationRollingSubjectRoleAssignmentCrudController;
use App\Administering\Provider\Admin\AdministrationRuntimeSourceNavigationProvider;
use App\Administering\ProviderInterface\Admin\AdministrationServiceToolMenuSectionProviderInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;

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
                static fn ($item): MenuItem => MenuItem::linkToRoute($item->label, $item->icon, $item->routeName)
                    ->setPermission($item->permission),
                $this->runtimeSourceNavigationProvider->items(),
            ));

        yield MenuItem::subMenu('Rolling ACL', 'fa fa-shield-alt')
            ->setPermission('administration.rolling.subject_access_report.view')
            ->setSubItems([
                MenuItem::linkTo(AdministrationRollingRoleCrudController::class, 'Roles', 'fa fa-users-cog'),
                MenuItem::linkTo(AdministrationRollingRoleHierarchyCrudController::class, 'Role hierarchy', 'fa fa-sitemap'),
                MenuItem::linkTo(AdministrationRollingRolePermissionCrudController::class, 'Role permissions', 'fa fa-key'),
                MenuItem::linkTo(AdministrationRollingSubjectRoleAssignmentCrudController::class, 'Subject assignments', 'fa fa-user-lock'),
                MenuItem::linkTo(AdministrationRollingAclRuleCrudController::class, 'ACL rules', 'fa fa-balance-scale'),
                MenuItem::linkTo(AdministrationRollingPermissionCrudController::class, 'Permission catalog', 'fa fa-list-check')
                    ->setPermission('administration.rolling.permission_catalog.view'),
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
