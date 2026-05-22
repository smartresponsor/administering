<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentDiagnosticReport;

interface AdministrationConnectedComponentDiagnosticReportProviderInterface
{
    public function report(): AdministrationConnectedComponentDiagnosticReport;
}
