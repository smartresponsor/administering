<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Service\Security;

use App\Administering\Service\Security\BootstrapAdministrationExternalPermissionDecisionProvider;
use App\Administering\Service\Security\RollingAdministrationPermissionChecker;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\AdministrationCurrentUserContext;
use PHPUnit\Framework\TestCase;

final class RollingAdministrationPermissionCheckerTest extends TestCase
{
    public function testBootstrapAdminRoleCanSeeAdministrationShell(): void
    {
        $checker = new RollingAdministrationPermissionChecker(
            new class implements AdministrationCurrentUserContextProviderInterface {
                public function current(): ?AdministrationCurrentUserContext
                {
                    return new AdministrationCurrentUserContext(
                        'accessing:account:1',
                        'admin@example.com',
                        ['ROLE_ADMIN'],
                    );
                }
            },
            new BootstrapAdministrationExternalPermissionDecisionProvider(),
        );

        self::assertTrue($checker->isGranted('administration.dashboard.view'));
        self::assertTrue($checker->isGranted('administration.operation.view'));
        self::assertTrue($checker->isGranted('administration.rolling.permission_catalog.view'));
    }

    public function testMissingCurrentUserIsDenied(): void
    {
        $checker = new RollingAdministrationPermissionChecker(
            new class implements AdministrationCurrentUserContextProviderInterface {
                public function current(): ?AdministrationCurrentUserContext
                {
                    return null;
                }
            },
            new BootstrapAdministrationExternalPermissionDecisionProvider(),
        );

        self::assertFalse($checker->isGranted('administration.dashboard.view'));
    }
}
