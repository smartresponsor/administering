<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Safe, operator-facing remediation item for a connected administration component.
 */
final readonly class AdministrationConnectedComponentRemediationItem
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $component,
        private string $key,
        private string $severity,
        private string $title,
        private string $recommendation,
        private bool $blocksMutations = false,
        private array $context = [],
    ) {
    }

    public function component(): string
    {
        return $this->component;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function severity(): string
    {
        return $this->severity;
    }

    public function blocksMutations(): bool
    {
        return $this->blocksMutations;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'component' => $this->component,
            'key' => $this->key,
            'severity' => $this->severity,
            'title' => $this->title,
            'recommendation' => $this->recommendation,
            'blocksMutations' => $this->blocksMutations,
            'context' => $this->context,
        ];
    }
}
