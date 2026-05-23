<?php

declare(strict_types=1);

namespace App\Administering\Provider\Security;

use App\Administering\ProviderInterface\Security\AdministrationExternalPermissionDecisionProviderInterface;

/**
 * Safe default provider used before the host wires Rolling as the decision authority.
 */
final class BootstrapAdministrationExternalPermissionDecisionProvider implements AdministrationExternalPermissionDecisionProviderInterface
{
    public function decide(string $subjectIdentifier, string $permission, string $scope, array $context = []): bool
    {
        return false;
    }
}
