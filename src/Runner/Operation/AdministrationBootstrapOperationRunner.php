<?php

declare(strict_types=1);

namespace App\Administering\Runner\Operation;

use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Safe bootstrap runner used until the host application wires concrete operation executors.
 */
final class AdministrationBootstrapOperationRunner implements AdministrationOperationRunnerInterface
{
    public function supportedOperationTypes(): array
    {
        return [];
    }

    public function run(string $operationKey, ?string $operationType = null): AdministrationOperationExecutionResult
    {
        return AdministrationOperationExecutionResult::skipped(
            'No host operation runner is wired for Administering yet.',
            ['operation_key' => $operationKey, 'operation_type' => $operationType, 'runner' => self::class],
        );
    }
}
