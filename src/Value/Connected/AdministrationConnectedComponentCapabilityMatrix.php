<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Aggregated metadata-only capability matrix for connected administration components.
 */
final readonly class AdministrationConnectedComponentCapabilityMatrix
{
    /**
     * @param list<AdministrationConnectedComponentCapability> $capabilities
     * @param list<string>                                     $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $capabilities,
        private array $guards = [],
    ) {
    }

    /** @return array<string, int> */
    private function countByComponent(): array
    {
        $counts = [];
        foreach ($this->capabilities as $capability) {
            $component = $capability->component();
            $counts[$component] = ($counts[$component] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /** @return array<string, int> */
    private function countByStatus(): array
    {
        $counts = [];
        foreach ($this->capabilities as $capability) {
            $status = $capability->status();
            $counts[$status] = ($counts[$status] ?? 0) + 1;
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
                'totalCapabilities' => count($this->capabilities),
                'sensitiveCapabilities' => count(array_filter(
                    $this->capabilities,
                    static fn (AdministrationConnectedComponentCapability $capability): bool => $capability->sensitive(),
                )),
                'mutationCapabilities' => count(array_filter(
                    $this->capabilities,
                    static fn (AdministrationConnectedComponentCapability $capability): bool => $capability->mutation(),
                )),
                'byComponent' => $this->countByComponent(),
                'byStatus' => $this->countByStatus(),
            ],
            'capabilities' => array_map(
                static fn (AdministrationConnectedComponentCapability $capability): array => $capability->toSafeArray(),
                $this->capabilities,
            ),
            'guards' => $this->guards,
        ];
    }
}
