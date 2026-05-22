<?php

declare(strict_types=1);

namespace App\Administering\Value\Credential;

final readonly class AdministrationCredentialOperationResult
{
    /** @param list<string> $messages */
    public function __construct(
        private bool $successful,
        private string $operation,
        private string $credentialKey,
        private string $environment,
        private array $messages = [],
    ) {
    }

    public function successful(): bool
    {
        return $this->successful;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function credentialKey(): string
    {
        return $this->credentialKey;
    }

    public function environment(): string
    {
        return $this->environment;
    }

    /** @return list<string> */
    public function messages(): array
    {
        return $this->messages;
    }
}
