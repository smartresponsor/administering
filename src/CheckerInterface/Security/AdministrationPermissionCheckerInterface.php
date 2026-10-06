<?php

declare(strict_types=1);

namespace App\Administering\CheckerInterface\Security;

/**
 * Defines the permission-decision boundary used by Administering surfaces and workflows.
 */
interface AdministrationPermissionCheckerInterface
{
    /**
     * Decides whether a permission is granted for the supplied scope and safe decision context.
     *
     * @param array<string, mixed> $context
     */
    public function isGranted(string $permission, string $scope = 'administering:global', array $context = []): bool;
}
