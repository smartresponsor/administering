<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\Value\Operation\AdministrationOperationDispatchResult;

interface AdministrationOperationQueueInterface
{
    public function dispatch(AdministrationOperationRun $operationRun): AdministrationOperationDispatchResult;
}
