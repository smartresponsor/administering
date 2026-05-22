<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Read-only diagnostic scenario for Managing field visibility explainability screens.
 */
final readonly class AdministrationFieldVisibilityExplanationScenario
{
    /** @param list<string> $trace */
    public function __construct(
        public string $scenarioKey,
        public string $label,
        public string $decisionAxis,
        public string $finalDecision,
        public array $trace,
        public string $safetyNote,
    ) {
    }
}
