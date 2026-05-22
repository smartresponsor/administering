<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Host-wired boundary that executes a persisted Administering operation.
 *
 * The operation key is the persisted run identifier and is safe to pass through
 * Messenger. The operation type is the semantic executor selector stored in
 * system SQLite. Implementations must keep artifacts and status updates linked
 * to the operation key, not only to the operation type.
 */
interface AdministrationOperationRunnerInterface
{
    /** @return list<string> */
    public function supportedOperationTypes(): array;

    public function run(string $operationKey, ?string $operationType = null): AdministrationOperationExecutionResult;
}
