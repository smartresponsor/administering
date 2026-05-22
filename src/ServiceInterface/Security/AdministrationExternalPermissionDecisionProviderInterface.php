<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Security;

interface AdministrationExternalPermissionDecisionProviderInterface
{
    /** @param array<string, mixed> $context */
    public function decide(string $subjectIdentifier, string $permission, string $scope, array $context = []): bool;
}
