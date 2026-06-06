<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeExportResult
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $lockPath,
        public string $source,
        public array $payload,
    ) {
    }

    public function enabledBundleTokenCount(): int
    {
        return is_array($this->payload['enabledBundleTokens'] ?? null) ? count($this->payload['enabledBundleTokens']) : 0;
    }

    public function disabledComponentCount(): int
    {
        return is_array($this->payload['disabledComponents'] ?? null) ? count($this->payload['disabledComponents']) : 0;
    }
}
