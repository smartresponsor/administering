<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Unified metadata-only contract descriptor for connected components.
 */
final readonly class AdministrationConnectedComponentContract
{
    /** @param array<string, mixed> $context */
    public function __construct(
        private string $component,
        private string $key,
        private string $label,
        private string $category,
        private string $status,
        private string $provider,
        private string $consumer,
        private bool $required,
        private bool $sensitive,
        private string $runtimeMode,
        private array $context = [],
    ) {
    }

    public function component(): string
    {
        return $this->component;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function required(): bool
    {
        return $this->required;
    }

    public function sensitive(): bool
    {
        return $this->sensitive;
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
            'provider' => $this->provider,
            'consumer' => $this->consumer,
            'required' => $this->required,
            'sensitive' => $this->sensitive,
            'runtimeMode' => $this->runtimeMode,
            'context' => $this->context,
        ];
    }
}
