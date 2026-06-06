<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentExecutionPlanProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentExecutionPlan;
use App\Administering\Value\Connected\AdministrationConnectedComponentExecutionStep;

/** Builds an evidence-only execution plan. */
final readonly class AdministrationConnectedComponentExecutionPlanProvider implements AdministrationConnectedComponentExecutionPlanProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function plan(): AdministrationConnectedComponentExecutionPlan
    {
        $steps = [];
        foreach ($this->projection->decisionRows() as $row) {
            $component = $row->component;
            $blocked = 'missing_package' === $row->status;
            $steps[] = new AdministrationConnectedComponentExecutionStep(
                component: $component,
                key: $component.'.runtime_scope.inspect',
                title: 'Inspect '.$component.' runtime-scope evidence',
                stage: 'runtime_scope',
                executionType: 'read_only',
                blocked: $blocked,
                requiresReview: $blocked,
                safeToAutomate: !$blocked,
                sourceWorkItem: $component.'.runtime_scope.verify',
                context: $row->toArray(),
            );
        }

        return new AdministrationConnectedComponentExecutionPlan(new \DateTimeImmutable(), $steps, [
            'Execution plan is read-only until the component is present in composer inventory and enabled by lock evidence.',
        ]);
    }
}
