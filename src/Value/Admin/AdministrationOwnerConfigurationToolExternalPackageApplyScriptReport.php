<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Non-destructive PowerShell overlay script generation report for owner-side packages.
 *
 * The report describes generated script inputs only. It does not authorize deletes,
 * moves, namespace rewrites, or repository-wide cleanup operations.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport
{
    /**
     * @param list<array<string, mixed>>  $componentPlans
     * @param list<array<string, string>> $issues
     */
    public function __construct(
        public string $overlayPlanPath,
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
            $count += count($plan['overlayFiles'] ?? []);
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
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_apply_script.v1',
            'overlayPlanPath' => $this->overlayPlanPath,
            'planAccepted' => $this->planAccepted,
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'componentCount' => $this->componentCount(),
            'fileCount' => $this->fileCount(),
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'componentPlans' => $this->componentPlans,
            'issues' => $this->issues,
        ];
    }
}
