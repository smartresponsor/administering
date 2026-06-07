<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldVisibilityInspectionPrepareRequest;
use App\Administering\Value\Managing\ManagingFieldVisibilityInspectionPrepareResult;

interface AdministrationFieldVisibilityInspectionPrepareServiceInterface
{
    public function prepare(ManagingFieldVisibilityInspectionPrepareRequest $request): ManagingFieldVisibilityInspectionPrepareResult;
}
