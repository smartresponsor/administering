<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Connected;

use App\Administering\Value\Connected\AdministrationConnectedComponentRemediationPlan;

interface AdministrationConnectedComponentRemediationPlanProviderInterface
{
    public function plan(): AdministrationConnectedComponentRemediationPlan;
}
