<?php

declare(strict_types=1);

namespace App\Administering\Provider\Admin;

use App\Administering\Value\Admin\AdministrationRuntimeScopeSourceNavigationItem;

/**
 * Central navigation map for Runtime Scope source index actions.
 *
 * This is intentionally an EasyAdmin/Administering UI map only. It is not a
 * runtime-scope source of truth and it must not decide enabled components.
 */
final readonly class AdministrationRuntimeSourceNavigationProvider
{
    /** @return list<AdministrationRuntimeScopeSourceNavigationItem> */
    public function items(): array
    {
        return [
            new AdministrationRuntimeScopeSourceNavigationItem(
                'Composer inventory',
                'fa fa-cubes',
                'administration_composer_index',
            ),
            new AdministrationRuntimeScopeSourceNavigationItem(
                'APP_RUNTIME_SCOPE',
                'fa fa-sliders-h',
                'administration_runtime_scope_index',
            ),
            new AdministrationRuntimeScopeSourceNavigationItem(
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
            static fn (AdministrationRuntimeScopeSourceNavigationItem $item): array => $item->toTemplateNavigation(),
            $this->items(),
        );
    }
}
