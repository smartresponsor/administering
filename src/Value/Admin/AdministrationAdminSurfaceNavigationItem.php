<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Single admin-surface navigation projection shared by EasyAdmin menu and
 * admin-surface controllers. The class is UI metadata only; business logic must
 * stay in the paired index service behind the route action.
 */
final readonly class AdministrationAdminSurfaceNavigationItem
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
