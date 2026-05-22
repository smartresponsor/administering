<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Metadata-only work item aggregated from connected administration components.
 */
final readonly class AdministrationConnectedComponentWorkItem
{
    /**
     * @param list<string>         $dependsOn
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $component,
        private string $key,
        private string $title,
        private string $stage,
        private string $priority,
        private string $actionType,
        private bool $blocksMutation,
        private array $dependsOn = [],
        private array $context = [],
    ) {
    }

    public function component(): string
    {
        return $this->component;
    }

    public function priority(): string
    {
        return $this->priority;
    }

    public function blocksMutation(): bool
    {
        return $this->blocksMutation;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'component' => $this->component,
            'key' => $this->key,
            'title' => $this->title,
            'stage' => $this->stage,
            'priority' => $this->priority,
            'actionType' => $this->actionType,
            'blocksMutation' => $this->blocksMutation,
            'dependsOn' => $this->dependsOn,
            'context' => $this->context,
        ];
    }
}
