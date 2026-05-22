<?php

declare(strict_types=1);

namespace App\Administering\Value\Connected;

/**
 * Metadata-only readiness report for connected administration components.
 */
final readonly class AdministrationConnectedComponentReadinessReport
{
    /**
     * @param array<string, mixed> $accessing
     * @param array<string, mixed> $rolling
     * @param list<string>         $warnings
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $accessing,
        private array $rolling,
        private array $warnings = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function accessing(): array
    {
        return $this->accessing;
    }

    /** @return array<string, mixed> */
    public function rolling(): array
    {
        return $this->rolling;
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
            'accessing' => $this->accessing,
            'rolling' => $this->rolling,
            'warnings' => $this->warnings,
        ];
    }
}
