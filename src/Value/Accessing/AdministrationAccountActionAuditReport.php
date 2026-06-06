<?php

declare(strict_types=1);

namespace App\Administering\Value\Accessing;

/**
 * Administering-owned safe report for Accessing controlled account-action audit.
 */
final readonly class AdministrationAccountActionAuditReport
{
    /**
     * @param array<string, mixed>                             $filter
     * @param list<AdministrationAccountActionAuditProjection> $items
     */
    public function __construct(
        private array $filter,
        private AdministrationAccountActionAuditSummary $summary,
        private array $items,
    ) {
    }

    /** @return array<string, mixed> */
    public function filter(): array
    {
        return $this->filter;
    }

    public function summary(): AdministrationAccountActionAuditSummary
    {
        return $this->summary;
    }

    /** @return list<AdministrationAccountActionAuditProjection> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'filter' => $this->filter,
            'summary' => $this->summary->toSafeArray(),
            'items' => array_map(
                static fn (AdministrationAccountActionAuditProjection $item): array => $item->toSafeArray(),
                $this->items,
            ),
        ];
    }
}
