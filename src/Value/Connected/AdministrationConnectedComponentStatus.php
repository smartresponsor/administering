<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Metadata-only status projection for a connected component surface.
 */
final readonly class AdministrationConnectedComponentStatus
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        private string $component,
        private string $status,
        private string $message,
        private array $metadata = [],
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

    public function message(): string
    {
        return $this->message;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'component' => $this->component,
            'status' => $this->status,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
