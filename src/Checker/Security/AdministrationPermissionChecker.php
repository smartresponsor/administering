<?php

declare(strict_types=1);

namespace App\Administering\Checker\Security;

use App\Administering\CheckerInterface\Security\AdministrationPermissionCheckerInterface;
use App\Administering\Provider\Security\AdministrationBootstrapExternalPermissionDecisionProvider;
use App\Administering\ProviderInterface\Security\AdministrationExternalPermissionDecisionProviderInterface;
use App\Administering\ServiceInterface\Security\AdministrationCurrentUserContextProviderInterface;

/**
 * Administering permission checker.
 *
 * Bootstrap roles can open the admin shell. Application decisions are delegated
 * to the host-wired external decision provider through a local security port.
 */
final class AdministrationPermissionChecker implements AdministrationPermissionCheckerInterface
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
        return $this->externalDecisionProvider instanceof AdministrationBootstrapExternalPermissionDecisionProvider;
    }
}
