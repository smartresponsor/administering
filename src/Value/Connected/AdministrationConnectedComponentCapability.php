<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Unified metadata-only capability descriptor for connected components.
 */
final readonly class AdministrationConnectedComponentCapability
{
    /** @param array<string, mixed> $context */
    public function __construct(
        private string $component,
        private string $key,
        private string $label,
        private string $category,
        private string $status,
        private bool $sensitive,
        private bool $mutation,
        private bool $requiresReview,
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

    public function sensitive(): bool
    {
        return $this->sensitive;
    }

    public function mutation(): bool
    {
        return $this->mutation;
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
            'sensitive' => $this->sensitive,
            'mutation' => $this->mutation,
            'requiresReview' => $this->requiresReview,
            'context' => $this->context,
        ];
    }
}
