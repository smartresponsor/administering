<?php

declare(strict_types=1);

namespace App\Administering\Provider\Admin;

use App\Administering\Value\Admin\AdministrationAdminSurfaceNavigationItem;

/**
 * Central navigation map for Runtime Scope admin-surface index actions.
 *
 * This is intentionally an EasyAdmin/Administering UI map only. It is not a
 * runtime-scope source of truth and it must not decide enabled components.
 */
final readonly class AdministrationRuntimeSourceNavigationProvider
{
    /** @return list<AdministrationAdminSurfaceNavigationItem> */
    public function items(): array
    {
        return [
            new AdministrationAdminSurfaceNavigationItem(
                'Composer inventory',
                'fa fa-cubes',
                'administration_composer_index',
            ),
            new AdministrationAdminSurfaceNavigationItem(
                'APP_RUNTIME_SCOPE',
                'fa fa-sliders-h',
                'administration_runtime_scope_index',
            ),
            new AdministrationAdminSurfaceNavigationItem(
                'Runtime locks',
                'fa fa-lock',
                'administration_runtime_lock_index',
            ),
        ];
    }

    /** @return list<array{label:string,route:string}> */
    public function templateNavigation(): array
    {
        return array_map(
            static fn (AdministrationAdminSurfaceNavigationItem $item): array => $item->toTemplateNavigation(),
            $this->items(),
        );
    }
}
