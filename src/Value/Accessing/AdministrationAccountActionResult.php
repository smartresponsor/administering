<?php

declare(strict_types=1);

namespace App\Administering\Value\Accessing;

/**
 * Administering-owned result value for queued/recorded account action requests.
 */
final readonly class AdministrationAccountActionResult
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $status,
        private string $safeMessage,
        private array $safeContext = [],
    ) {
    }

    public static function recorded(): self
    {
        return new self('recorded', 'Request recorded for Accessing-owned execution.', [
            'execution_owner' => 'accessing',
            'executed_by_administering' => false,
        ]);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function succeeded(): bool
    {
        return in_array($this->status, ['ok', 'success', 'recorded'], true);
    }
}
