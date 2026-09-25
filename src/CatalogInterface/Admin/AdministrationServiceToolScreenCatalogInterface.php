<?php

declare(strict_types=1);

namespace App\Administering\CatalogInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolScreen;

/**
 * Resolves presentation metadata for administration tools without coupling callers to screen discovery.
 */
interface AdministrationServiceToolScreenCatalogInterface
{
    /**
     * Resolves a single screen definition for a tool, or null when no screen is declared.
     */
    public function screenForTool(string $section, string $toolShortName): ?AdministrationServiceToolScreen;

    /**
     * Returns screen definitions keyed by tool identity for one section.
     *
     * @return array<string, AdministrationServiceToolScreen>
     */
    public function screensForSection(string $section): array;
}
