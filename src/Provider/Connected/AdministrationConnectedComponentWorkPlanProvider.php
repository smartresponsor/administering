<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentWorkPlanProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentWorkItem;
use App\Administering\Value\Connected\AdministrationConnectedComponentWorkPlan;

/** Builds work plan without asking foreign components for work items. */
final readonly class AdministrationConnectedComponentWorkPlanProvider implements AdministrationConnectedComponentWorkPlanProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function plan(): AdministrationConnectedComponentWorkPlan
    {
        $items = [];
        foreach ($this->projection->decisionRows() as $row) {
            $component = $row->component;
            $items[] = new AdministrationConnectedComponentWorkItem(
                component: $component,
                key: $component.'.runtime_scope.verify',
                title: 'Verify '.$component.' runtime-scope evidence',
                stage: 'runtime_scope',
                priority: 'missing_package' === $row->status ? 'p1' : 'p3',
                actionType: 'read_only_audit',
                blocksMutation: 'missing_package' === $row->status,
                dependsOn: [],
                context: $row->toArray(),
            );
        }

        return new AdministrationConnectedComponentWorkPlan(new \DateTimeImmutable(), $items, [
            'No foreign provider/service/container references are allowed.',
            'Mutations require evidence from lock files and local Administering audit only.',
        ]);
    }
}
