<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Unified metadata-only execution plan for connected administration components.
 */
final readonly class AdministrationConnectedComponentExecutionPlan
{
    /**
     * @param list<AdministrationConnectedComponentExecutionStep> $steps
     * @param list<string>                                        $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $steps,
        private array $guards = [],
    ) {
    }

    /** @return list<AdministrationConnectedComponentExecutionStep> */
    public function steps(): array
    {
        return $this->steps;
    }

    /** @return list<AdministrationConnectedComponentExecutionStep> */
    public function blockedSteps(): array
    {
        return array_values(array_filter(
            $this->steps,
            static fn (AdministrationConnectedComponentExecutionStep $step): bool => $step->blocked(),
        ));
    }

    /** @return array<string, int> */
    private function countByComponent(): array
    {
        $counts = [];
        foreach ($this->steps as $step) {
            $component = $step->component();
            $counts[$component] = ($counts[$component] ?? 0) + 1;
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
                'totalSteps' => count($this->steps),
                'blockedSteps' => count($this->blockedSteps()),
                'automationEligibleSteps' => count(array_filter(
                    $this->steps,
                    static fn (AdministrationConnectedComponentExecutionStep $step): bool => $step->safeToAutomate(),
                )),
                'byComponent' => $this->countByComponent(),
            ],
            'steps' => array_map(
                static fn (AdministrationConnectedComponentExecutionStep $step): array => $step->toSafeArray(),
                $this->steps,
            ),
            'guards' => $this->guards,
        ];
    }
}
