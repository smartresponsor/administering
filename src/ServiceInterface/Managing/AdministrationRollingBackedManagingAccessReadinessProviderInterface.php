<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationRollingBackedManagingAccessReadinessReport;

interface AdministrationRollingBackedManagingAccessReadinessProviderInterface
{
    public function report(): AdministrationRollingBackedManagingAccessReadinessReport;
}
