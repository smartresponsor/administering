<?php

declare(strict_types=1);

namespace App\Administering\Service\Security;

use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Security\AdministrationExternalPermissionDecisionProviderInterface;
use App\Administering\ServiceInterface\Security\AdministrationPermissionCheckerInterface;

/**
 * Administering permission checker.
 *
 * Bootstrap roles can open the admin shell, but application ACL decisions should
 * be delegated to the external decision provider wired by the host app.
 */
final class RollingAdministrationPermissionChecker implements AdministrationPermissionCheckerInterface
{
    public function __construct(
        private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider,
        private readonly AdministrationExternalPermissionDecisionProviderInterface $externalDecisionProvider,
    ) {
    }

    public function isGranted(string $permission, string $scope = 'administering:global', array $context = []): bool
    {
        $current = $this->currentUserContextProvider->current();
        if (null === $current) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $current->bootstrapRoles(), true)) {
            return true;
        }

        if ($this->usesBootstrapFallback() && $this->hasBootstrapAdminRole($current->bootstrapRoles())) {
            return true;
        }

        if (
            'administration.dashboard.view' === $permission
            && $this->hasBootstrapAdminRole($current->bootstrapRoles())
        ) {
            return true;
        }

        return $this->externalDecisionProvider->decide(
            $current->subjectIdentifier(),
            $permission,
            $scope,
            $context + [
                'user_identifier' => $current->userIdentifier(),
                'bootstrap_roles' => $current->bootstrapRoles(),
            ],
        );
    }

    /** @param list<string> $bootstrapRoles */
    private function hasBootstrapAdminRole(array $bootstrapRoles): bool
    {
        return in_array('ROLE_ADMIN', $bootstrapRoles, true) || in_array('ROLE_ADMIN_BOOTSTRAP', $bootstrapRoles, true);
    }

    private function usesBootstrapFallback(): bool
    {
        return $this->externalDecisionProvider instanceof BootstrapAdministrationExternalPermissionDecisionProvider;
    }
}
