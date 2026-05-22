<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Security;

interface AdministrationPermissionCheckerInterface
{
    /** @param array<string, mixed> $context */
    public function isGranted(string $permission, string $scope = 'administering:global', array $context = []): bool;
}
