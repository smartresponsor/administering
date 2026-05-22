<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentReadinessReport;

interface AdministrationConnectedComponentReadinessReportProviderInterface
{
    public function report(): AdministrationConnectedComponentReadinessReport;
}
