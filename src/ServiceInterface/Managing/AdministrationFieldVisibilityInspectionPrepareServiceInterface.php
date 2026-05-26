<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldVisibilityInspectionPrepareRequest;
use App\Managing\Value\Administration\ManagingFieldVisibilityInspectionPrepareResult;

interface AdministrationFieldVisibilityInspectionPrepareServiceInterface
{
    public function prepare(ManagingFieldVisibilityInspectionPrepareRequest $request): ManagingFieldVisibilityInspectionPrepareResult;
}
