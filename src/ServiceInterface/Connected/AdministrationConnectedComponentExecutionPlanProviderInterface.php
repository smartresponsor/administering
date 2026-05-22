<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentExecutionPlan;

interface AdministrationConnectedComponentExecutionPlanProviderInterface
{
    public function plan(): AdministrationConnectedComponentExecutionPlan;
}
