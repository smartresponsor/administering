<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Aggregated, metadata-only overview for connected component admin surfaces.
 */
final readonly class AdministrationConnectedComponentOverview
{
    /** @param list<AdministrationConnectedComponentStatus> $statuses */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $statuses,
    ) {
    }

    public function generatedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    /** @return list<AdministrationConnectedComponentStatus> */
    public function statuses(): array
    {
        return $this->statuses;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'statuses' => array_map(
                static fn (AdministrationConnectedComponentStatus $status): array => $status->toSafeArray(),
                $this->statuses,
            ),
        ];
    }
}
