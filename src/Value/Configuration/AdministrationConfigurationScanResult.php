<?php

declare(strict_types=1);

namespace App\Administering\Value\Configuration;

final readonly class AdministrationConfigurationScanResult
{
    /**
     * @param list<AdministrationConfigurationEntry> $entries
     * @param list<string>                           $warnings
     */
    public function __construct(
        private string $rootPath,
        private array $entries,
        private array $warnings = [],
    ) {
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    /** @return list<AdministrationConfigurationEntry> */
    public function entries(): array
    {
        return $this->entries;
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /** @return list<array<string, mixed>> */
    public function entriesAsArray(): array
    {
        return array_map(
            static fn (AdministrationConfigurationEntry $entry): array => $entry->toArray(),
            $this->entries,
        );
    }
}
