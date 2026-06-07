<?php

declare(strict_types=1);

namespace App\Administering\Provider\Managing;

use App\Administering\ServiceInterface\Managing\AdministrationRollingBackedManagingAccessReadinessProviderInterface;
use App\Administering\Value\Managing\AdministrationRollingBackedManagingAccessReadinessReport;

final readonly class AdministrationDryRollingBackedManagingAccessReadinessProvider implements AdministrationRollingBackedManagingAccessReadinessProviderInterface
{
    public function report(): AdministrationRollingBackedManagingAccessReadinessReport
    {
        return new AdministrationRollingBackedManagingAccessReadinessReport(
            false,
            'administering_self_contained_dry_runtime',
            ['Rolling-backed Managing access is not connected in standalone dry-runtime.'],
            ['owner_managing_runtime', 'owner_rolling_runtime'],
        );
    }
}
