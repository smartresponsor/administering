<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only handoff report describing how owner-side configuration tools should
 * be packaged for neighboring repositories.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageReport
{
    /**
     * @param list<array<string, mixed>> $providers
     * @param list<array<string, mixed>> $entries
     * @param list<array<string, mixed>> $rejectedEntries
     */
    public function __construct(
        public array $providers,
        public array $entries,
        public array $rejectedEntries,
    ) {
    }

    public function providerCount(): int
    {
        return count($this->providers);
    }

    public function entryCount(): int
    {
        return count($this->entries);
    }

    public function rejectedCount(): int
    {
        return count($this->rejectedEntries);
    }

    public function hasRejectedEntries(): bool
    {
        return [] !== $this->rejectedEntries;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_configuration_external_package.v1',
            'providerCount' => $this->providerCount(),
            'entryCount' => $this->entryCount(),
            'rejectedCount' => $this->rejectedCount(),
            'providers' => $this->providers,
            'entries' => $this->entries,
            'rejectedEntries' => $this->rejectedEntries,
        ];
    }
}
