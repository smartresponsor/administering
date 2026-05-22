<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Administering-owned metadata-only view of Rolling ACL execution report data.
 */
final readonly class AdministrationAclMutationExecutionReport
{
    /**
     * @param array<string, mixed>       $filter
     * @param array<string, mixed>       $summary
     * @param list<array<string, mixed>> $events
     */
    public function __construct(
        private array $filter,
        private array $summary,
        private array $events = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function filter(): array
    {
        return $this->filter;
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        return $this->summary;
    }

    /** @return list<array<string, mixed>> */
    public function events(): array
    {
        return $this->events;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'filter' => $this->filter,
            'summary' => $this->summary,
            'events' => $this->events,
        ];
    }
}
