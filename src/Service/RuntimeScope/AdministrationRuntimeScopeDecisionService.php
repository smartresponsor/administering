<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Reader\RuntimeScope\AdministrationRuntimeScopeStateReader;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecision;

/**
 * Central runtime-scope decision service.
 *
 * This service combines Composer capability-boundary evidence and runtime-scope
 * lock evidence into component decisions. APP_RUNTIME_SCOPE is legacy display
 * vocabulary only and must not act as runtime decision authority.
 */
final readonly class AdministrationRuntimeScopeDecisionService
{
    public function __construct(
        private AdministrationRuntimeScopeStateReader $stateReader,
        private AdministrationRuntimeComponentStatusService $statusService,
    ) {
    }

    public function decide(string $hostDir, string $environment): AdministrationRuntimeScopeDecision
    {
        $state = $this->stateReader->read($hostDir, $environment);

        return new AdministrationRuntimeScopeDecision(
            state: $state,
            componentStatuses: $this->statusService->statuses($state),
        );
    }
}
