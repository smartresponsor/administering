<?php

declare(strict_types=1);

namespace App\Administering\Value\Accessing;

/**
 * Administering-owned request value for controlled account action records.
 */
final readonly class AdministrationAccountActionRequest
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $accountReference,
        private string $requestedBySubject,
        private string $safeReason,
        private array $safeContext = [],
    ) {
    }

    public function action(): string
    {
        return $this->action;
    }

    public function accountReference(): string
    {
        return $this->accountReference;
    }

    public function requestedBySubject(): string
    {
        return $this->requestedBySubject;
    }

    public function safeReason(): string
    {
        return $this->safeReason;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }
}
