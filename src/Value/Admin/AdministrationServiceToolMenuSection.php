<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only EasyAdmin menu projection for one Administering service-tool section.
 *
 * The projection is enriched from the SQLite materialized tool index when
 * available, but it keeps the static catalog metadata for icons and permissions.
 */
final readonly class AdministrationServiceToolMenuSection
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public string $permission,
        public int $toolCount,
        public int $executableCount,
        public int $formReadyCount,
        public int $indexedOnlyCount,
        public string $status,
        public bool $databaseBacked,
    ) {
    }

    public function menuLabel(): string
    {
        if (0 >= $this->toolCount) {
            return $this->label;
        }

        return sprintf('%s (%d)', $this->label, $this->toolCount);
    }
}
