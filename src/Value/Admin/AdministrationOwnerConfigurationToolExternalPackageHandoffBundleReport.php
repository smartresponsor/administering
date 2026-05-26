<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Reviewed handoff bundle report for owner-side external package overlays.
 *
 * This value object intentionally describes only non-destructive overlay handoff
 * metadata. It does not authorize deletes, repository resets, or automatic moves.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport
{
    /**
     * @param list<array<string, mixed>>  $componentPlans
     * @param list<array<string, string>> $issues
     */
    public function __construct(
        public string $overlayPlanPath,
        public ?string $manifestPath,
        public ?string $validationPath,
        public ?string $applyScriptPath,
        public bool $planAccepted,
        public array $componentPlans,
        public array $issues,
    ) {
    }

    public function componentCount(): int
    {
        return count($this->componentPlans);
    }

    public function fileCount(): int
    {
        $count = 0;
        foreach ($this->componentPlans as $plan) {
            $count += (int) ($plan['overlayFileCount'] ?? count($plan['overlayFiles'] ?? []));
        }

        return $count;
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
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_handoff_bundle.v1',
            'overlayPlanPath' => $this->overlayPlanPath,
            'manifestPath' => $this->manifestPath,
            'validationPath' => $this->validationPath,
            'applyScriptPath' => $this->applyScriptPath,
            'planAccepted' => $this->planAccepted,
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'manualReviewRequired' => true,
            'componentCount' => $this->componentCount(),
            'fileCount' => $this->fileCount(),
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'componentPlans' => $this->componentPlans,
            'issues' => $this->issues,
        ];
    }
}
