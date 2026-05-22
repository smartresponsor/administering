<?php

declare(strict_types=1);

namespace App\Administering\Message;

/**
 * Messenger envelope for a persisted Administering operation run.
 *
 * The message carries only the operation key. The operation payload must be read
 * from the system SQLite storage by the worker, preventing secrets from leaking
 * into transport payloads or logs.
 */
final readonly class AdministrationOperationRunMessage
{
    public function __construct(private string $operationKey)
    {
    }

    public function operationKey(): string
    {
        return $this->operationKey;
    }
}
