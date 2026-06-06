<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Single runtime-scope source navigation projection shared by EasyAdmin menu and
 * runtime-scope source controllers. The class is UI metadata only; business logic must
 * stay in the paired index service behind the route action.
 */
final readonly class AdministrationRuntimeScopeSourceNavigationItem
{
    public function __construct(
        public string $label,
        public string $icon,
        public string $routeName,
        public string $permission = 'administration.dashboard.view',
    ) {
    }

    /** @return array{label:string,route:string} */
    public function toTemplateNavigation(): array
    {
        return [
            'label' => $this->label,
            'route' => $this->routeName,
        ];
    }
}
