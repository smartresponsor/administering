<?php

declare(strict_types=1);

namespace App\Administering\Service\Connected;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeDecisionService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecision;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecisionRow;

/**
 * Builds connected-component read models from Administering-owned runtime evidence only.
 *
 * This service never asks foreign components for PHP services or classes. It reads only
 * APP_ENV, APP_RUNTIME_SCOPE, composer inventory, and runtime-scope lock files.
 */
final readonly class AdministrationRuntimeScopeConnectedComponentProjectionService
{
    public function __construct(
        private string $projectDir,
        private string $environment,
        private AdministrationRuntimeScopeDecisionService $decisionService,
    ) {
    }

    /** @return list<AdministrationRuntimeScopeDecisionRow> */
    public function decisionRows(): array
    {
        return $this->decision()->decisionRows();
    }

    /** @return array<string, AdministrationRuntimeScopeDecisionRow> */
    public function decisionRowsByComponent(): array
    {
        return $this->decision()->decisionRowsByComponent();
    }

    /** @return list<array<string, mixed>> */
    public function componentRows(): array
    {
        return $this->decision()->componentRows();
    }

    /** @return list<string> */
    public function sourceErrors(): array
    {
        return $this->decision()->sourceErrors();
    }

    /** @return array<string, mixed> */
    public function sourceSummary(): array
    {
        return $this->decision()->sourceSummary();
    }

    private function decision(): AdministrationRuntimeScopeDecision
    {
        return $this->decisionService->decide($this->projectDir, $this->environment);
    }
}
