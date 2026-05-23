<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes one top-level Administering service section.
 *
 * The left EasyAdmin menu is intentionally aligned with these service-section
 * directories instead of listing every internal review/apply/diagnostic route.
 */
final readonly class AdministrationServiceSection
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public string $serviceDirectory,
        public string $primaryCrudControllerClass,
        public string $permission,
    ) {
    }
}
