<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentWorkPlan;

interface AdministrationConnectedComponentWorkPlanProviderInterface
{
    public function plan(): AdministrationConnectedComponentWorkPlan;
}
