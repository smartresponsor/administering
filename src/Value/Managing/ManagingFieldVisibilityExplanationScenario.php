<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldVisibilityExplanationScenario
{
    /** @param list<string> $matchingAxes */
    public function __construct(
        public string $key,
        public string $title,
        public string $symptom,
        public string $likelyCause,
        public string $operatorAction,
        public array $matchingAxes = [],
    ) {
    }
}
