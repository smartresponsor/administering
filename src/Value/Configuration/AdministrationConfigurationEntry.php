<?php

declare(strict_types=1);

namespace App\Administering\Value\Configuration;

/**
 * Normalized, safe-to-store view of one detected configuration value.
 *
 * The value is intentionally nullable/masked because snapshots must not become
 * a secondary credential store.
 */
final readonly class AdministrationConfigurationEntry
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        private string $key,
        private string $sourceType,
        private string $sourcePath,
        private ?string $componentName,
        private ?string $displayValue,
        private bool $secret,
        private bool $writable,
        private array $metadata = [],
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function sourcePath(): string
    {
        return $this->sourcePath;
    }

    public function componentName(): ?string
    {
        return $this->componentName;
    }

    public function displayValue(): ?string
    {
        return $this->displayValue;
    }

    public function secret(): bool
    {
        return $this->secret;
    }

    public function writable(): bool
    {
        return $this->writable;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'source_type' => $this->sourceType,
            'source_path' => $this->sourcePath,
            'component_name' => $this->componentName,
            'display_value' => $this->displayValue,
            'secret' => $this->secret,
            'writable' => $this->writable,
            'metadata' => $this->metadata,
        ];
    }
}
