<?php

declare(strict_types=1);

namespace App\Administering\Builder\Admin;

use App\Administering\BuilderInterface\Admin\AdministrationMainMenuBuilderInterface;
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
    ) {
    }

    public function build(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home')
            ->setPermission('administration.dashboard.view');

        yield MenuItem::linkToUrl('Configuration Center', 'fa fa-cogs', '/admin/config')
            ->setPermission('administration.config.view');

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
