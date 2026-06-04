<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Accessing;

use App\Accessing\Value\Admin\AccessAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessAccountAdministrationResult;
use App\Administering\Entity\AdministrationAccountActionRequestRecord;

interface AdministrationAccountActionRequestRecorderInterface
{
    public function record(
        AccessAccountAdministrationRequest $request,
        AccessAccountAdministrationResult $result,
    ): AdministrationAccountActionRequestRecord;
}
