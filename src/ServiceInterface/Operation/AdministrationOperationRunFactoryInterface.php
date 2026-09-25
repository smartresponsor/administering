<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Value\Operation\AdministrationOperationPlan;

interface AdministrationOperationRunFactoryInterface
{
    public function createForCurrentUser(AdministrationOperationPlan $plan): AdministrationOperationRunEntity;
}
