<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Rolling\AdministrationFieldVisibilityInspectionPrepareRequest;
use App\Administering\Value\Rolling\AdministrationFieldVisibilityInspectionPrepareResult;

interface AdministrationFieldVisibilityInspectionPrepareServiceInterface
{
    public function prepare(AdministrationFieldVisibilityInspectionPrepareRequest $request): AdministrationFieldVisibilityInspectionPrepareResult;
}
