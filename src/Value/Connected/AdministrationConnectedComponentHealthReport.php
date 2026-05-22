<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Unified metadata-only health report for connected administration components.
 */
final readonly class AdministrationConnectedComponentHealthReport
{
    /**
     * @param list<AdministrationConnectedComponentHealthCheck> $checks
     * @param list<string>                                      $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $checks,
        private array $guards = [],
    ) {
    }

    /** @return array<string, int> */
    private function countBy(string $method): array
    {
        $counts = [];
        foreach ($this->checks as $check) {
            $key = (string) $check->{$method}();
            $counts[$key] = ($counts[$key] ?? 0) + 1;
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
                'totalChecks' => count($this->checks),
                'blockingChecks' => count(array_filter(
                    $this->checks,
                    static fn (AdministrationConnectedComponentHealthCheck $check): bool => $check->blocking(),
                )),
                'byStatus' => $this->countBy('status'),
                'bySeverity' => $this->countBy('severity'),
            ],
            'checks' => array_map(
                static fn (AdministrationConnectedComponentHealthCheck $check): array => $check->toSafeArray(),
                $this->checks,
            ),
            'guards' => $this->guards,
        ];
    }
}
