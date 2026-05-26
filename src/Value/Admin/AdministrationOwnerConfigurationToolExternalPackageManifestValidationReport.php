<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only validation report for owner-side external package manifests.
 *
 * The report intentionally validates delivery safety invariants and does not
 * authorize applying, moving, deleting, or rewriting files in neighboring repositories.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageManifestValidationReport
{
    /**
     * @param list<array<string, mixed>> $issues
     * @param list<array<string, mixed>> $componentSummaries
     */
    public function __construct(
        public string $manifestPath,
        public bool $schemaAccepted,
        public array $issues,
        public array $componentSummaries,
    ) {
    }

    public function issueCount(): int
    {
        return count($this->issues);
    }

    public function errorCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'error' === ($issue['severity'] ?? null)));
    }

    public function warningCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'warning' === ($issue['severity'] ?? null)));
    }

    public function componentCount(): int
    {
        return count($this->componentSummaries);
    }

    public function hasErrors(): bool
    {
        return 0 < $this->errorCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_manifest_validation.v1',
            'manifestPath' => $this->manifestPath,
            'schemaAccepted' => $this->schemaAccepted,
            'componentCount' => $this->componentCount(),
            'issueCount' => $this->issueCount(),
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'componentSummaries' => $this->componentSummaries,
            'issues' => $this->issues,
        ];
    }
}
