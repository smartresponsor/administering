<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Security;

use App\Administering\Checker\Security\AdministrationPermissionChecker;
use App\Administering\Provider\Security\AdministrationBootstrapExternalPermissionDecisionProvider;
use App\Administering\ServiceInterface\Security\AdministrationCurrentUserContextProviderInterface;
use App\Administering\Value\AdministrationCurrentUserContext;
use PHPUnit\Framework\TestCase;

final class AdministrationPermissionCheckerTest extends TestCase
{
    public function testBootstrapAdminRoleCanSeeAdministrationShell(): void
    {
        $checker = new AdministrationPermissionChecker(
            new class implements AdministrationCurrentUserContextProviderInterface {
                public function current(): AdministrationCurrentUserContext
                {
                    return new AdministrationCurrentUserContext(
                        'symfony:user:1',
                        'admin@example.com',
                        ['ROLE_ADMIN'],
                    );
                }
            },
            new AdministrationBootstrapExternalPermissionDecisionProvider(),
        );

        self::assertTrue($checker->isGranted('administration.dashboard.view'));
        self::assertTrue($checker->isGranted('administration.operation.view'));
        self::assertTrue($checker->isGranted('administration.connected_component.overview.view'));
    }

    public function testMissingCurrentUserIsDenied(): void
    {
        $checker = new AdministrationPermissionChecker(
            new class implements AdministrationCurrentUserContextProviderInterface {
                public function current(): ?AdministrationCurrentUserContext
                {
                    return null;
                }
            },
            new AdministrationBootstrapExternalPermissionDecisionProvider(),
        );

        self::assertFalse($checker->isGranted('administration.dashboard.view'));
    }
}
