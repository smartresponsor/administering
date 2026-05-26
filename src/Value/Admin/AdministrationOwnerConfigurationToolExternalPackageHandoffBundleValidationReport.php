<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Validation report for a generated owner-side external handoff bundle.
 *
 * The report intentionally validates only the handoff metadata and safety
 * contract. It does not apply files to owner repositories.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidationReport
{
    /**
     * @param list<array<string, string>> $issues
     */
    public function __construct(
        public string $handoffDir,
        public bool $readmeExists,
        public bool $checklistExists,
        public bool $reportExists,
        public bool $safetyContractAccepted,
        public int $componentCount,
        public int $fileCount,
        public array $issues,
    ) {
    }

    public function errorCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'error' === ($issue['severity'] ?? null)));
    }

    public function warningCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'warning' === ($issue['severity'] ?? null)));
    }

    public function hasErrors(): bool
    {
        return 0 < $this->errorCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_handoff_bundle_validation.v1',
            'handoffDir' => $this->handoffDir,
            'readmeExists' => $this->readmeExists,
            'checklistExists' => $this->checklistExists,
            'reportExists' => $this->reportExists,
            'safetyContractAccepted' => $this->safetyContractAccepted,
            'componentCount' => $this->componentCount,
            'fileCount' => $this->fileCount,
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'issues' => $this->issues,
        ];
    }
}
