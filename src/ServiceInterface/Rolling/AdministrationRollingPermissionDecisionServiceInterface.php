<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

interface AdministrationRollingPermissionDecisionServiceInterface
{
    /** @param array<string, mixed> $context */
    public function isGranted(string $subjectIdentifier, string $permission, string $scope, array $context = []): bool;
}
