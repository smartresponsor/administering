<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingAclMutationApplyResult
{
    public function __construct(
        private string $requestKey,
        private bool $succeeded,
        private string $status,
        private string $safeMessage,
        /** @var array<string, mixed> */
        private array $safeContext = [],
    ) {
    }

    /** @param array<string, mixed> $safeContext */
    public static function fromRollingResult(string $requestKey, bool $succeeded, string $status, string $safeMessage, array $safeContext = []): self
    {
        return new self($requestKey, $succeeded, $status, $safeMessage, $safeContext);
    }

    /** @param array<string, mixed> $safeContext */
    public static function skipped(string $requestKey, string $safeMessage, array $safeContext = []): self
    {
        return new self($requestKey, false, 'skipped', $safeMessage, $safeContext);
    }

    /** @param array<string, mixed> $safeContext */
    public static function rejected(string $requestKey, string $safeMessage, array $safeContext = []): self
    {
        return new self($requestKey, false, 'rejected', $safeMessage, $safeContext);
    }

    public function requestKey(): string
    {
        return $this->requestKey;
    }

    public function succeeded(): bool
    {
        return $this->succeeded;
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

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'request_key' => $this->requestKey,
            'succeeded' => $this->succeeded,
            'status' => $this->status,
            'safe_message' => $this->safeMessage,
            'safe_context' => $this->safeContext,
        ];
    }
}
