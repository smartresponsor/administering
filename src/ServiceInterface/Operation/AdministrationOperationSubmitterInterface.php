<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Value\Operation\AdministrationOperationPlan;

/**
 * Creates, persists, and queues safe administrative operation runs.
 */
interface AdministrationOperationSubmitterInterface
{
    public function submitForCurrentUser(AdministrationOperationPlan $plan): AdministrationOperationRunEntity;
}
