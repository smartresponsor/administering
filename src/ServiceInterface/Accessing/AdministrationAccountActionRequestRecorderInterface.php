<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Accessing;

use App\Administering\Entity\AdministrationAccountActionRequestRecordEntity;
use App\Administering\Value\Accessing\AdministrationAccountActionRequest;
use App\Administering\Value\Accessing\AdministrationAccountActionResult;

interface AdministrationAccountActionRequestRecorderInterface
{
    public function record(
        AdministrationAccountActionRequest $request,
        AdministrationAccountActionResult $result,
    ): AdministrationAccountActionRequestRecordEntity;
}
