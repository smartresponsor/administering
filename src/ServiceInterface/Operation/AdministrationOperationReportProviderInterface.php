<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Value\Operation\AdministrationOperationReport;

interface AdministrationOperationReportProviderInterface
{
    public function reportFor(string $operationKey): AdministrationOperationReport;
}
