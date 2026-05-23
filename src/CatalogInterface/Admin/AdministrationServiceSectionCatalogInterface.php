<?php

declare(strict_types=1);

namespace App\Administering\CatalogInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceSection;

interface AdministrationServiceSectionCatalogInterface
{
    /** @return list<AdministrationServiceSection> */
    public function sections(): array;
}
