<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only transition decision report for the owner-side migration track.
 *
 * The report is intentionally advisory. It does not mutate repositories and it
 * does not apply external packages. It summarizes whether Administering can stop
 * adding internal ecosystem tools and move the next work to owner repository
 * current slices.
 */
final readonly class AdministrationOwnerConfigurationToolTransitionDecisionReport
{
    /**
     * @param list<array<string, mixed>>                                $tools
     * @param array<string, int>                                        $decisionCounts
     * @param list<array{severity:string, code:string, message:string}> $issues
     * @param list<string>                                              $recommendedNextActions
     */
    public function __construct(
        public ?string $componentFilter,
        public array $tools,
        public array $decisionCounts,
        public array $issues,
        public array $recommendedNextActions,
        public bool $externalPipelinePresent,
        public bool $handoffBundlePresent,
    ) {
    }

    public function toolCount(): int
    {
        return count($this->tools);
    }

    public function readyToPauseInternalWaveCount(): int
    {
        return $this->decisionCounts['ready_to_pause_internal_waves'] ?? 0;
    }

    public function needsOwnerRepositorySliceCount(): int
    {
        return $this->decisionCounts['needs_owner_repository_slice'] ?? 0;
    }

    public function needsHostApplicationSliceCount(): int
    {
        return $this->decisionCounts['needs_host_application_slice'] ?? 0;
    }

    public function keepInAdministeringCount(): int
    {
        return $this->decisionCounts['keep_in_administering_shell'] ?? 0;
    }

    public function warningCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'warning' === ($issue['severity'] ?? null)));
    }

    public function errorCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'error' === ($issue['severity'] ?? null)));
    }

    public function canStopInternalExpansion(): bool
    {
        return $this->externalPipelinePresent
            && $this->handoffBundlePresent
            && 0 === $this->errorCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_configuration_transition_decision.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'componentFilter' => $this->componentFilter,
            'toolCount' => $this->toolCount(),
            'readyToPauseInternalWaveCount' => $this->readyToPauseInternalWaveCount(),
            'needsOwnerRepositorySliceCount' => $this->needsOwnerRepositorySliceCount(),
            'needsHostApplicationSliceCount' => $this->needsHostApplicationSliceCount(),
            'keepInAdministeringCount' => $this->keepInAdministeringCount(),
            'warningCount' => $this->warningCount(),
            'errorCount' => $this->errorCount(),
            'externalPipelinePresent' => $this->externalPipelinePresent,
            'handoffBundlePresent' => $this->handoffBundlePresent,
            'canStopInternalExpansion' => $this->canStopInternalExpansion(),
            'decisionCounts' => $this->decisionCounts,
            'recommendedNextActions' => $this->recommendedNextActions,
            'tools' => $this->tools,
            'issues' => $this->issues,
        ];
    }
}
