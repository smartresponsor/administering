<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Reviewed overlay plan for future owner-side configuration tool packages.
 *
 * The report is intentionally non-destructive. It describes which repository-relative
 * files should be overlaid into neighboring owner repositories and which safety
 * assumptions were checked, but it never authorizes deletes, moves, or namespace rewrites.
 */
final readonly class AdministrationOwnerConfigurationToolExternalPackageOverlayPlanReport
{
    /**
     * @param list<array<string, mixed>> $componentPlans
     * @param list<array<string, mixed>> $issues
     */
    public function __construct(
        public string $manifestPath,
        public bool $manifestAccepted,
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

    public function hasErrors(): bool
    {
        return 0 < $this->errorCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_configuration_external_package_overlay_plan.v1',
            'manifestPath' => $this->manifestPath,
            'manifestAccepted' => $this->manifestAccepted,
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'componentCount' => $this->componentCount(),
            'fileCount' => $this->fileCount(),
            'issueCount' => $this->issueCount(),
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'componentPlans' => $this->componentPlans,
            'issues' => $this->issues,
        ];
    }
}
