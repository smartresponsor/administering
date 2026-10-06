<?php

declare(strict_types=1);

namespace App\Administering\CatalogInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceSection;

/**
 * Provides the operator-visible administration section inventory.
 */
interface AdministrationServiceSectionCatalogInterface
{
    /**
     * Returns sections available for administration navigation and tool grouping.
     *
     * @return list<AdministrationServiceSection>
     */
    public function sections(): array;
}
