<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Records operation status transitions in system storage using safe metadata only.
 */
interface AdministrationOperationStatusRecorderInterface
{
    public function markRunning(string $operationKey): void;

    public function markFinished(string $operationKey, AdministrationOperationExecutionResult $result): void;

    public function markFailed(string $operationKey, \Throwable $throwable): void;
}
