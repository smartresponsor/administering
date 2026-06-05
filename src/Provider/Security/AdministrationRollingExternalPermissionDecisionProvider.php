<?php

declare(strict_types=1);

namespace App\Administering\Provider\Security;

use App\Administering\ProviderInterface\Security\AdministrationExternalPermissionDecisionProviderInterface;
use App\Rolling\ServiceInterface\Administration\RollingAdministrationPermissionCatalogInterface;
use App\Rolling\ServiceInterface\Administration\RollingAdministrationPermissionDecisionServiceInterface;

/**
 * Delegates Administering permission decisions to Rolling when the component set is connected.
 *
 * The provider remains fail-closed: only permissions present in the Rolling administration
 * catalog are delegated to the PDP. This prevents a permissive/demo PDP from accidentally
 * granting a typo or future Administering gate that Rolling has not modeled yet.
 */
final class AdministrationRollingExternalPermissionDecisionProvider implements AdministrationExternalPermissionDecisionProviderInterface
{
    /** @var array<string, true>|null */
    private ?array $permissionIndex = null;

    public function __construct(
        private readonly RollingAdministrationPermissionDecisionServiceInterface $decisionService,
        private readonly RollingAdministrationPermissionCatalogInterface $permissionCatalog,
    ) {
    }

    public function decide(string $subjectIdentifier, string $permission, string $scope, array $context = []): bool
    {
        $subjectIdentifier = trim($subjectIdentifier);
        $permission = trim($permission);
        $scope = trim($scope);

        if ('' === $subjectIdentifier || '' === $permission) {
            return false;
        }

        if (!$this->isCataloguedPermission($permission)) {
            return false;
        }

        return $this->decisionService->isGranted(
            $subjectIdentifier,
            $permission,
            '' !== $scope ? $scope : 'administering:global',
            $context + ['administration_permission_catalogued' => true],
        );
    }

    private function isCataloguedPermission(string $permission): bool
    {
        if (null === $this->permissionIndex) {
            $this->permissionIndex = [];
            foreach ($this->permissionCatalog->permissions() as $cataloguedPermission) {
                $cataloguedPermission = trim($cataloguedPermission);
                if ('' !== $cataloguedPermission) {
                    $this->permissionIndex[$cataloguedPermission] = true;
                }
            }
        }

        return isset($this->permissionIndex[$permission]);
    }
}
