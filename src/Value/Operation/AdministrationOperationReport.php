<?php

declare(strict_types=1);

namespace App\Administering\Value\Operation;

/**
 * Safe UI/report projection for an Administering operation.
 *
 * The report is intentionally metadata-only. It may be rendered in native
 * Symfony/EasyAdmin screens and exported as JSON without leaking credentials,
 * raw configuration dumps, sessions, password hashes, or 2FA internals.
 */
final readonly class AdministrationOperationReport
{
    /**
     * @param list<array<string, mixed>> $events
     * @param list<array<string, mixed>> $artifacts
     * @param array<string, mixed>       $safeContext
     */
    public function __construct(
        private string $operationKey,
        private string $status,
        private string $safeTitle,
        private array $events = [],
        private array $artifacts = [],
        private array $safeContext = [],
    ) {
    }

    public function operationKey(): string
    {
        return $this->operationKey;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function safeTitle(): string
    {
        return $this->safeTitle;
    }

    /** @return list<array<string, mixed>> */
    public function events(): array
    {
        return $this->events;
    }

    /** @return list<array<string, mixed>> */
    public function artifacts(): array
    {
        return $this->artifacts;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'operation_key' => $this->operationKey,
            'status' => $this->status,
            'safe_title' => $this->safeTitle,
            'events' => $this->events,
            'artifacts' => $this->artifacts,
            'safe_context' => $this->safeContext,
        ];
    }
}
