<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes one tool implemented by a concrete service class.
 *
 * Service tools are discovered from src/Service/<Section> so section dashboards
 * can be populated from the same catalog that defines the left menu sections.
 */
final readonly class AdministrationServiceTool
{
    public function __construct(
        public string $section,
        public string $serviceClass,
        public string $shortName,
        public string $label,
        public string $kind,
        public ?string $primaryRouteName = null,
        public ?string $primaryRouteLabel = null,
    ) {
    }

    public function hasPrimaryRoute(): bool
    {
        return null !== $this->primaryRouteName;
    }
}
