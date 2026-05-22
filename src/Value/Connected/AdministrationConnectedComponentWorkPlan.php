<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Unified metadata-only work plan for Accessing/Rolling/Administering hardening.
 */
final readonly class AdministrationConnectedComponentWorkPlan
{
    /**
     * @param list<AdministrationConnectedComponentWorkItem> $items
     * @param list<string>                                   $safeExecutionRules
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $items,
        private array $safeExecutionRules = [],
    ) {
    }

    /** @return list<AdministrationConnectedComponentWorkItem> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return list<AdministrationConnectedComponentWorkItem> */
    public function blockers(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (AdministrationConnectedComponentWorkItem $item): bool => $item->blocksMutation(),
        ));
    }

    /** @return array<string, int> */
    private function countByPriority(): array
    {
        $counts = [];
        foreach ($this->items as $item) {
            $priority = $item->priority();
            $counts[$priority] = ($counts[$priority] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'summary' => [
                'totalItems' => count($this->items),
                'blockers' => count($this->blockers()),
                'byPriority' => $this->countByPriority(),
            ],
            'items' => array_map(
                static fn (AdministrationConnectedComponentWorkItem $item): array => $item->toSafeArray(),
                $this->items,
            ),
            'safeExecutionRules' => $this->safeExecutionRules,
        ];
    }
}
