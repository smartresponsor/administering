<?php

declare(strict_types=1);

namespace App\Administering\Value\Operation;

/**
 * Safe dispatch result for long-running Administering operations.
 */
final readonly class AdministrationOperationDispatchResult
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private bool $dispatched,
        private string $operationKey,
        private string $status,
        private array $safeContext = [],
    ) {
    }

    public static function queued(string $operationKey): self
    {
        return new self(true, $operationKey, 'queued');
    }

    /** @param array<string, mixed> $safeContext */
    public static function rejected(string $operationKey, string $safeReason, array $safeContext = []): self
    {
        return new self(false, $operationKey, 'rejected', $safeContext + ['reason' => $safeReason]);
    }

    public function dispatched(): bool
    {
        return $this->dispatched;
    }

    public function operationKey(): string
    {
        return $this->operationKey;
    }

    public function status(): string
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }
}
