<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Final advisory gate for pausing the internal Administering transition waves.
 *
 * This value object is intentionally read-only. It does not apply external
 * packages and it does not mutate owner repositories. It only records whether
 * the Administering shell has enough discovery, validation, projection, and
 * handoff infrastructure to stop expanding internal ecosystem tools.
 */
final readonly class AdministrationOwnerConfigurationToolTransitionPauseGateReport
{
    /**
     * @param array<string, int>                                        $classificationCounts
     * @param list<array<string, mixed>>                                $classifications
     * @param list<array{severity:string, code:string, message:string}> $issues
     * @param list<string>                                              $recommendedNextActions
     */
    public function __construct(
        public ?string $componentFilter,
        public array $classificationCounts,
        public array $classifications,
        public array $issues,
        public array $recommendedNextActions,
        public bool $externalPipelineReportPresent,
        public bool $handoffBundlePresent,
        public bool $handoffBundleValidationPresent,
        public bool $transitionDecisionReportPresent,
    ) {
    }

    public function toolCount(): int
    {
        return count($this->classifications);
    }

    public function ownerRepositoryCandidateCount(): int
    {
        return $this->classificationCounts['owner_repository_candidate'] ?? 0;
    }

    public function hostApplicationCandidateCount(): int
    {
        return $this->classificationCounts['host_application_candidate'] ?? 0;
    }

    public function adminShellOwnedCount(): int
    {
        return $this->classificationCounts['admin_shell_owned'] ?? 0;
    }

    public function ownerProvidedCount(): int
    {
        return $this->classificationCounts['owner_provided'] ?? 0;
    }

    public function warningCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'warning' === ($issue['severity'] ?? null)));
    }

    public function errorCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'error' === ($issue['severity'] ?? null)));
    }

    public function canPauseInternalWaves(): bool
    {
        return $this->externalPipelineReportPresent
            && $this->handoffBundlePresent
            && $this->handoffBundleValidationPresent
            && 0 === $this->errorCount();
    }

    public function nextWorkMode(): string
    {
        if (!$this->canPauseInternalWaves()) {
            return 'finish_transition_infrastructure';
        }

        if (0 < $this->ownerRepositoryCandidateCount() || 0 < $this->hostApplicationCandidateCount()) {
            return 'request_owner_and_host_current_slices';
        }

        return 'maintain_administering_shell';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.owner_configuration_transition_pause_gate.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'componentFilter' => $this->componentFilter,
            'toolCount' => $this->toolCount(),
            'ownerProvidedCount' => $this->ownerProvidedCount(),
            'adminShellOwnedCount' => $this->adminShellOwnedCount(),
            'ownerRepositoryCandidateCount' => $this->ownerRepositoryCandidateCount(),
            'hostApplicationCandidateCount' => $this->hostApplicationCandidateCount(),
            'warningCount' => $this->warningCount(),
            'errorCount' => $this->errorCount(),
            'externalPipelineReportPresent' => $this->externalPipelineReportPresent,
            'handoffBundlePresent' => $this->handoffBundlePresent,
            'handoffBundleValidationPresent' => $this->handoffBundleValidationPresent,
            'transitionDecisionReportPresent' => $this->transitionDecisionReportPresent,
            'canPauseInternalWaves' => $this->canPauseInternalWaves(),
            'nextWorkMode' => $this->nextWorkMode(),
            'classificationCounts' => $this->classificationCounts,
            'recommendedNextActions' => $this->recommendedNextActions,
            'classifications' => $this->classifications,
            'issues' => $this->issues,
        ];
    }
}
