<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\Value\Operation\AdministrationOperationPlan;

/**
 * Converts a submitted tool form into a safe operation plan.
 */
interface AdministrationServiceToolOperationPlanFactoryInterface
{
    public function createForSubmittedTool(AdministrationServiceToolRecord $record, mixed $formData): AdministrationOperationPlan;
}
