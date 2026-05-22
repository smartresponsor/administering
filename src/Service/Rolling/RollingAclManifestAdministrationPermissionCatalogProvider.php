<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationPermissionDescriptor;
use App\Rolling\ServiceInterface\Administration\RollingAclManifestBuilderInterface;
use App\Rolling\Value\Administration\RollingAclManifestPermission;

/**
 * Reads Rolling's safe ACL manifest and exposes it to Administering screens.
 */
final class RollingAclManifestAdministrationPermissionCatalogProvider implements AdministrationPermissionCatalogProviderInterface
{
    public function __construct(private readonly RollingAclManifestBuilderInterface $manifestBuilder)
    {
    }

    /** @return list<AdministrationPermissionDescriptor> */
    public function descriptors(): array
    {
        return array_map(
            static fn (RollingAclManifestPermission $permission): AdministrationPermissionDescriptor => new AdministrationPermissionDescriptor(
                $permission->key(),
                $permission->label(),
                $permission->category(),
                $permission->scopes(),
                $permission->sensitive(),
            ),
            $this->manifestBuilder->build()->permissions(),
        );
    }
}
