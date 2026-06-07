<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class AdministrationRollingBackedManagingAccessReadinessReport
{
    /**
     * @param list<string> $warnings
     * @param list<string> $missingCapabilities
     */
    public function __construct(
        public bool $ready,
        public string $mode,
        public array $warnings = [],
        public array $missingCapabilities = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'ready' => $this->ready,
            'mode' => $this->mode,
            'warnings' => $this->warnings,
            'missing_capabilities' => $this->missingCapabilities,
        ];
    }
}
