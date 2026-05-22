<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Accessing;

use App\Accessing\Value\Admin\AccessingAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessingAccountAdministrationResult;
use App\Administering\Entity\AdministrationAccountActionRequestRecord;

interface AdministrationAccountActionRequestRecorderInterface
{
    public function record(
        AccessingAccountAdministrationRequest $request,
        AccessingAccountAdministrationResult $result,
    ): AdministrationAccountActionRequestRecord;
}
