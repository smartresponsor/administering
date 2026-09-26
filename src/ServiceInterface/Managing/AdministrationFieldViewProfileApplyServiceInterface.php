<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileApplyRequest;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileApplyResult;

interface AdministrationFieldViewProfileApplyServiceInterface
{
    public function prepare(AdministrationManagingFieldViewProfileApplyRequest $request): AdministrationManagingFieldViewProfileApplyResult;
}
