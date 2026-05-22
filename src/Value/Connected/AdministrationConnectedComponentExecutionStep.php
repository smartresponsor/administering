<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Metadata-only execution step aggregated from connected administration components.
 */
final readonly class AdministrationConnectedComponentExecutionStep
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $component,
        private string $key,
        private string $title,
        private string $stage,
        private string $executionType,
        private bool $blocked,
        private bool $requiresReview,
        private bool $safeToAutomate,
        private string $sourceWorkItem,
        private array $context = [],
    ) {
    }

    public function component(): string
    {
        return $this->component;
    }

    public function blocked(): bool
    {
        return $this->blocked;
    }

    public function safeToAutomate(): bool
    {
        return $this->safeToAutomate;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'component' => $this->component,
            'key' => $this->key,
            'title' => $this->title,
            'stage' => $this->stage,
            'executionType' => $this->executionType,
            'blocked' => $this->blocked,
            'requiresReview' => $this->requiresReview,
            'safeToAutomate' => $this->safeToAutomate,
            'sourceWorkItem' => $this->sourceWorkItem,
            'context' => $this->context,
        ];
    }
}
