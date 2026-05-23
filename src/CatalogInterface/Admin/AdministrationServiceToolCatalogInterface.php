<?php

declare(strict_types=1);

namespace App\Administering\CatalogInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceTool;

interface AdministrationServiceToolCatalogInterface
{
    /** @return list<AdministrationServiceTool> */
    public function tools(): array;

    /** @return list<AdministrationServiceTool> */
    public function toolsForSection(string $section): array;
}
