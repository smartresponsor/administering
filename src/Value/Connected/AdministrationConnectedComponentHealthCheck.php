<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Unified metadata-only health check descriptor for connected components.
 */
final readonly class AdministrationConnectedComponentHealthCheck
{
    /** @param array<string, mixed> $context */
    public function __construct(
        private string $component,
        private string $key,
        private string $label,
        private string $category,
        private string $status,
        private string $severity,
        private bool $blocking,
        private array $context = [],
    ) {
    }

    public function status(): string
    {
        return $this->status;
    }

    public function severity(): string
    {
        return $this->severity;
    }

    public function blocking(): bool
    {
        return $this->blocking;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'component' => $this->component,
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category,
            'status' => $this->status,
            'severity' => $this->severity,
            'blocking' => $this->blocking,
            'context' => $this->context,
        ];
    }
}
