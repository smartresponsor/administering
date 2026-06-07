<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationRollingPermissionDecisionServiceInterface;

final readonly class AdministrationDenyRollingPermissionDecisionService implements AdministrationRollingPermissionDecisionServiceInterface
{
    public function isGranted(string $subjectIdentifier, string $permission, string $scope, array $context = []): bool
    {
        return false;
    }
}
