<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessCatalogProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Rolling\AdministrationFieldAccessCatalogItem;
use App\Administering\Value\Rolling\AdministrationFieldAccessMatrixRow;
use App\Administering\Value\Rolling\AdministrationManagingFieldPermissionVocabulary;
use App\Administering\Value\Rolling\AdministrationPermissionDescriptor;

/**
 * Builds the read-only Administering view of Managing field-access capability metadata.
 */
final readonly class AdministrationManagingFieldAccessCatalogProvider implements AdministrationFieldAccessCatalogProviderInterface
{
    public function __construct(private AdministrationPermissionCatalogProviderInterface $permissionCatalogProvider)
    {
    }

    public function catalogItems(): array
    {
        $descriptors = [];
        foreach ($this->permissionCatalogProvider->descriptors() as $descriptor) {
            $descriptors[$descriptor->key()] = $descriptor;
        }

        $items = [];
        foreach (AdministrationManagingFieldPermissionVocabulary::policyKeys() as $permissionKey) {
            $descriptor = $descriptors[$permissionKey] ?? null;
            $items[] = new AdministrationFieldAccessCatalogItem(
                permissionKey: $permissionKey,
                label: $descriptor?->label() ?? $this->fallbackLabel($permissionKey),
                category: $descriptor?->category() ?? 'managing_field_access',
                controlPlaneGroup: $this->controlPlaneGroup($permissionKey),
                scopes: $descriptor?->scopes() ?? ['component', 'resource', 'field'],
                sensitive: $descriptor?->sensitive() ?? str_contains($permissionKey, '.configure') || str_contains($permissionKey, '.assign'),
                registeredInRolling: $descriptor instanceof AdministrationPermissionDescriptor,
            );
        }

        return $items;
    }

    public function matrixRows(): array
    {
        return [
            new AdministrationFieldAccessMatrixRow(10, 'System/component hard deny', 'Managing', 'deny', 'Cannot be overridden', 'Field not available on page, component hard deny, or backend denied config.'),
            new AdministrationFieldAccessMatrixRow(20, 'Effective security decision', 'Rolling', 'allow/deny/abstain', 'Deny wins', 'Roles, groups, direct subject rules, and inherited grants decide access.'),
            new AdministrationFieldAccessMatrixRow(30, 'Admin-assigned policy/profile', 'Administering', 'allow/deny/profile assignment', 'Deny wins over user preference', 'Control-plane surface for role, group, and user field policies.'),
            new AdministrationFieldAccessMatrixRow(40, 'User personal view profile', 'Managing', 'visible/hidden', 'May only narrow allowed fields', 'User can hide or show only fields already allowed by security/admin policy.'),
            new AdministrationFieldAccessMatrixRow(50, 'EasyAdmin rendering', 'Managing', 'render/not-render', 'Receives final field set only', 'Hidden or denied fields are not emitted as EasyAdmin fields.'),
        ];
    }

    private function fallbackLabel(string $permissionKey): string
    {
        return ucwords(str_replace(['.', '_'], ' ', $permissionKey));
    }

    private function controlPlaneGroup(string $permissionKey): string
    {
        if (AdministrationManagingFieldPermissionVocabulary::FIELD_VIEW === $permissionKey) {
            return 'field access';
        }

        if (AdministrationManagingFieldPermissionVocabulary::FIELD_CONFIGURE === $permissionKey) {
            return 'field policy configuration';
        }

        return 'view profile administration';
    }
}
