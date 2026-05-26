<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Executes a persisted service-tool operation run from safe metadata only.
 */
interface AdministrationServiceToolExecutorInterface
{
    public function execute(string $operationKey): AdministrationOperationExecutionResult;
}
