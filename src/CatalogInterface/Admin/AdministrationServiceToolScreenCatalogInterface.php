<?php

declare(strict_types=1);

namespace App\Administering\CatalogInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceToolScreen;

interface AdministrationServiceToolScreenCatalogInterface
{
    public function screenForTool(string $section, string $toolShortName): ?AdministrationServiceToolScreen;

    /** @return array<string, AdministrationServiceToolScreen> */
    public function screensForSection(string $section): array;
}
