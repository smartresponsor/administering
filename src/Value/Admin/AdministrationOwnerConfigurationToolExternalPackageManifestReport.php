<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only external package manifest for future owner-side configuration tool handoff.
 *
 * The manifest is intentionally non-destructive: it describes overlay targets for
 * neighboring repositories, but it does not authorize moving or deleting files.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageManifestReport
{
    /**
     * @param list<array<string, mixed>> $providers
     * @param list<array<string, mixed>> $componentManifests
     * @param list<array<string, mixed>> $rejectedEntries
     */
    public function __construct(
        public array $providers,
        public array $componentManifests,
        public array $rejectedEntries,
    ) {
    }

    public function providerCount(): int
    {
        return count($this->providers);
    }

    public function componentCount(): int
    {
        return count($this->componentManifests);
    }

    public function entryCount(): int
    {
        $count = 0;
        foreach ($this->componentManifests as $manifest) {
            $count += count($manifest['tools'] ?? []);
        }

        return $count;
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
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_manifest.v1',
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'providerCount' => $this->providerCount(),
            'componentCount' => $this->componentCount(),
            'entryCount' => $this->entryCount(),
            'rejectedCount' => $this->rejectedCount(),
            'providers' => $this->providers,
            'componentManifests' => $this->componentManifests,
            'rejectedEntries' => $this->rejectedEntries,
        ];
    }
}
