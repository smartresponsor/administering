<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Service\Managing\AdministrationRollingBackedManagingAccessReadinessProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationRollingBackedManagingAccessReadinessProviderTest extends TestCase
{
    public function testProvidesReadOnlyRollingBackedManagingAccessChecklist(): void
    {
        $report = (new AdministrationRollingBackedManagingAccessReadinessProvider())->report();
        $safe = $report->toSafeArray();

        self::assertSame('rolling', $safe['mode']);
        self::assertSame('deny', $safe['failure_effect']);
        self::assertSame('managing.field.view', $safe['permission_key']);
        self::assertFalse($safe['safety']['grants_access']);
        self::assertFalse($safe['safety']['mutates_rolling_acl']);
        self::assertGreaterThanOrEqual(6, count($safe['items']));
    }
}
