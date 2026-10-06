<?php

declare(strict_types=1);

namespace App\Administering\CatalogInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceTool;

/**
 * Defines the read boundary for discovered administration tools.
 */
interface AdministrationServiceToolCatalogInterface
{
    /**
     * Returns the complete validated tool inventory visible to Administering.
     *
     * @return list<AdministrationServiceTool>
     */
    public function tools(): array;

    /**
     * Returns the subset of tools belonging to one administration section.
     *
     * @return list<AdministrationServiceTool>
     */
    public function toolsForSection(string $section): array;
}
