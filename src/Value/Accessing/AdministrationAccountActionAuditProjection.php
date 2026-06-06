<?php

declare(strict_types=1);

namespace App\Administering\Value\Accessing;

/**
 * Administering-safe account action audit projection sourced from Accessing.
 */
final readonly class AdministrationAccountActionAuditProjection
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $accountReference,
        private string $requestedBySubject,
        private string $resultStatus,
        private string $safeMessage,
        private \DateTimeImmutable $createdAt,
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

    public function resultStatus(): string
    {
        return $this->resultStatus;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'action' => $this->action,
            'accountReference' => $this->accountReference,
            'requestedBySubject' => $this->requestedBySubject,
            'resultStatus' => $this->resultStatus,
            'safeMessage' => $this->safeMessage,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'safeContext' => $this->safeContext,
        ];
    }
}
