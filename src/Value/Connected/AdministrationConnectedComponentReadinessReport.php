<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Metadata-only readiness report for runtime-scope connected components.
 */
final readonly class AdministrationConnectedComponentReadinessReport
{
    /**
     * @param array<string, array<string, mixed>> $components
     * @param list<string>                        $warnings
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $components,
        private array $warnings = [],
    ) {
    }

    /** @return array<string, array<string, mixed>> */
    public function components(): array
    {
        return $this->components;
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'components' => $this->components,
            'warnings' => $this->warnings,
        ];
    }
}
