<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationPermissionDescriptor;

/**
 * Reads the permission catalog that Administering may visualize for ACL administration.
 */
interface AdministrationPermissionCatalogProviderInterface
{
    /** @return list<AdministrationPermissionDescriptor> */
    public function descriptors(): array;
}
