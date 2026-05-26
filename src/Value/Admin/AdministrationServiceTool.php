<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes one executable tool implemented by a canonical service class.
 *
 * A PHP file becomes a service tool only when the class follows the strict
 * Administration{Direction}{ToolSlug}Service convention and the namespace
 * matches src/Service/{Direction}. The filesystem is authoritative for tool
 * existence; SQLite records are only EasyAdmin projections of this value.
 */
final readonly class AdministrationServiceTool
{
    public function __construct(
        public string $section,
        public string $directionToken,
        public string $toolSlug,
        public string $toolKey,
        public string $serviceClass,
        public string $shortName,
        public string $serviceFile,
        public string $label,
        public string $kind,
        public string $operationType,
        public string $checksum,
        public ?string $formTypeClass = null,
        public ?string $formDataClass = null,
        public bool $executable = false,
        public ?string $primaryRouteName = null,
        public ?string $primaryRouteLabel = null,
        public string $sourceOwnership = 'administering_internal',
        public ?string $ownerComponentKey = null,
        public ?string $ownerComponentToken = null,
        public ?string $ownerProviderClass = null,
        public ?string $ownerServiceClass = null,
        public ?string $ownerSourceLabel = null,
    ) {
    }

    public function hasPrimaryRoute(): bool
    {
        return null !== $this->primaryRouteName;
    }

    public function hasForm(): bool
    {
        return null !== $this->formTypeClass;
    }

    public function hasFormDataClass(): bool
    {
        return null !== $this->formDataClass;
    }

    public function isExecutable(): bool
    {
        return $this->executable;
    }
}
