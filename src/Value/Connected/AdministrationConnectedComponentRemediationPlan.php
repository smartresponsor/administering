<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Aggregated metadata-only remediation plan for Accessing/Rolling/Administering readiness.
 */
final readonly class AdministrationConnectedComponentRemediationPlan
{
    /**
     * @param list<AdministrationConnectedComponentRemediationItem> $items
     * @param list<string>                                          $safeNextActions
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $items,
        private array $safeNextActions = [],
    ) {
    }

    /** @return list<AdministrationConnectedComponentRemediationItem> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return list<AdministrationConnectedComponentRemediationItem> */
    public function blockers(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (AdministrationConnectedComponentRemediationItem $item): bool => $item->blocksMutations(),
        ));
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'summary' => [
                'totalItems' => count($this->items),
                'blockers' => count($this->blockers()),
                'safeNextActionCount' => count($this->safeNextActions),
            ],
            'items' => array_map(
                static fn (AdministrationConnectedComponentRemediationItem $item): array => $item->toSafeArray(),
                $this->items,
            ),
            'safeNextActions' => $this->safeNextActions,
        ];
    }
}
