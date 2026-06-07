<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationRollingPermissionDescriptor;

interface AdministrationRollingPermissionCatalogInterface
{
    /** @return list<string> */
    public function permissions(): array;

    /** @return list<AdministrationRollingPermissionDescriptor> */
    public function descriptors(): array;
}
