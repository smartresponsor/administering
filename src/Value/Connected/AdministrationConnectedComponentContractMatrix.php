<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Aggregated metadata-only contract matrix for connected administration components.
 */
final readonly class AdministrationConnectedComponentContractMatrix
{
    /**
     * @param list<AdministrationConnectedComponentContract> $contracts
     * @param list<string>                                   $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $contracts,
        private array $guards = [],
    ) {
    }

    /** @return array<string, int> */
    private function countByComponent(): array
    {
        $counts = [];
        foreach ($this->contracts as $contract) {
            $component = $contract->component();
            $counts[$component] = ($counts[$component] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /** @return array<string, int> */
    private function countByStatus(): array
    {
        $counts = [];
        foreach ($this->contracts as $contract) {
            $status = $contract->status();
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
                'totalContracts' => count($this->contracts),
                'requiredContracts' => count(array_filter(
                    $this->contracts,
                    static fn (AdministrationConnectedComponentContract $contract): bool => $contract->required(),
                )),
                'sensitiveContracts' => count(array_filter(
                    $this->contracts,
                    static fn (AdministrationConnectedComponentContract $contract): bool => $contract->sensitive(),
                )),
                'byComponent' => $this->countByComponent(),
                'byStatus' => $this->countByStatus(),
            ],
            'contracts' => array_map(
                static fn (AdministrationConnectedComponentContract $contract): array => $contract->toSafeArray(),
                $this->contracts,
            ),
            'guards' => $this->guards,
        ];
    }
}
