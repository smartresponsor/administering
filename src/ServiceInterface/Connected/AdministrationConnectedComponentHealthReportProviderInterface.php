<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentHealthReport;

/**
 * Provides a unified metadata-only health report for connected components.
 */
interface AdministrationConnectedComponentHealthReportProviderInterface
{
    public function report(): AdministrationConnectedComponentHealthReport;
}
