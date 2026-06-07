<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationRollingPermissionCatalogInterface;
use App\Administering\Value\Managing\ManagingFieldPermissionVocabulary;
use App\Administering\Value\Rolling\AdministrationRollingPermissionDescriptor;

final readonly class AdministrationStaticRollingPermissionCatalogProvider implements AdministrationRollingPermissionCatalogInterface
{
    /** @return list<string> */
    public function permissions(): array
    {
        return array_map(static fn (AdministrationRollingPermissionDescriptor $descriptor): string => $descriptor->key(), $this->descriptors());
    }

    /** @return list<AdministrationRollingPermissionDescriptor> */
    public function descriptors(): array
    {
        $descriptors = [];
        foreach (ManagingFieldPermissionVocabulary::policyKeys() as $permissionKey) {
            $descriptors[] = new AdministrationRollingPermissionDescriptor(
                $permissionKey,
                ucwords(str_replace(['.', '_'], ' ', $permissionKey)),
                'managing_field_access',
                ['component', 'resource', 'field'],
                str_contains($permissionKey, '.configure') || str_contains($permissionKey, '.assign'),
            );
        }

        return $descriptors;
    }
}
