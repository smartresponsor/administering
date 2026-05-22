<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationFieldViewProfileApplyRequest;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileApplyResult;

interface AdministrationFieldViewProfileApplyServiceInterface
{
    public function prepare(AdministrationFieldViewProfileApplyRequest $request): AdministrationFieldViewProfileApplyResult;
}
