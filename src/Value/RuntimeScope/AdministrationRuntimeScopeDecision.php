<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeDecision
{
    /**
     * @param list<AdministrationRuntimeComponentStatus> $componentStatuses
     */
    public function __construct(
        public AdministrationRuntimeScopeState $state,
        public array $componentStatuses,
    ) {
    }

    /** @return list<string> */
    public function sourceErrors(): array
    {
        return $this->state->sourceErrors;
    }

    /** @return array<string, mixed> */
    public function sourceSummary(): array
    {
        return [
            'hostDir' => $this->state->hostDir,
            'environment' => $this->state->environment,
            'appRuntimeScope' => $this->state->appRuntimeScopeRaw ?? '',
            'appRuntimeScopeTokens' => $this->state->appRuntimeScope,
            'composerFile' => $this->state->composerFile,
            'composerPath' => $this->state->composerPath,
            'composerPackageCount' => count($this->state->composerPackages),
            'lockPath' => $this->state->lockPath,
            'lockPresent' => $this->state->lockPresent,
            'enabledBundleTokenCount' => count($this->state->enabledBundleTokens),
            'enabledComponentCount' => count($this->state->enabledComponents),
            'disabledComponentCount' => count($this->state->disabledComponents),
            'installedComponentCount' => count($this->state->installedComponents),
            'sourceErrors' => $this->state->sourceErrors,
        ];
    }

    /** @return list<AdministrationRuntimeScopeDecisionRow> */
    public function decisionRows(): array
    {
        $rows = [];
        foreach ($this->componentStatuses as $status) {
            $rows[] = AdministrationRuntimeScopeDecisionRow::fromStatus($status, $this->state->appRuntimeScopeRaw);
        }

        if ([] === $rows) {
            $rows[] = AdministrationRuntimeScopeDecisionRow::standalone($this->state->appRuntimeScopeRaw);
        }

        return $rows;
    }

    /** @return array<string, AdministrationRuntimeScopeDecisionRow> */
    public function decisionRowsByComponent(): array
    {
        $rows = [];
        foreach ($this->decisionRows() as $row) {
            $rows[$row->component] = $row;
        }

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function componentRows(): array
    {
        return array_map(
            static fn (AdministrationRuntimeScopeDecisionRow $row): array => $row->toArray(),
            $this->decisionRows(),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'administering.runtime_scope.output.v1',
            'report' => 'administration_runtime_scope_decision',
            'source' => $this->sourceSummary(),
            'components' => $this->componentRows(),
            'errors' => $this->sourceErrors(),
            'warnings' => [],
            'sourceErrors' => $this->sourceErrors(),
        ];
    }
}
