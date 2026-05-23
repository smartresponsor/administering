<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Rolling\AdministrationRollingBackedManagingAccessReadinessReport;

/**
 * Provides read-only readiness data for Rolling-backed Managing field access activation.
 */
interface AdministrationRollingBackedManagingAccessReadinessProviderInterface
{
    public function report(): AdministrationRollingBackedManagingAccessReadinessReport;
}
