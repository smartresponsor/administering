<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\Rolling;

use App\Administering\Provider\Managing\AdministrationDryRollingBackedManagingAccessReadinessProvider;
use PHPUnit\Framework\TestCase;

final class AdministrationRollingBackedManagingAccessReadinessProviderTest extends TestCase
{
    public function testProvidesReadOnlyRollingBackedManagingAccessChecklist(): void
    {
        $report = (new AdministrationDryRollingBackedManagingAccessReadinessProvider())->report();
        $safe = $report->toSafeArray();

        self::assertFalse($safe['ready']);
        self::assertSame('administering_self_contained_dry_runtime', $safe['mode']);
        self::assertContains('owner_managing_runtime', $safe['missing_capabilities']);
        self::assertContains('owner_rolling_runtime', $safe['missing_capabilities']);
    }
}
