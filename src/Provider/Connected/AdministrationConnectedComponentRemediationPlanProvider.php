<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentRemediationPlanProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentRemediationItem;
use App\Administering\Value\Connected\AdministrationConnectedComponentRemediationPlan;

/** Creates safe remediation from runtime evidence. */
final readonly class AdministrationConnectedComponentRemediationPlanProvider implements AdministrationConnectedComponentRemediationPlanProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function plan(): AdministrationConnectedComponentRemediationPlan
    {
        $items = [];
        foreach ($this->projection->decisionRows() as $row) {
            if ('missing_package' === $row->status) {
                $items[] = new AdministrationConnectedComponentRemediationItem(
                    component: $row->component,
                    key: $row->component.'.install_package_or_remove_scope',
                    severity: 'high',
                    title: 'Runtime scope requests a component whose package is missing.',
                    recommendation: 'Install the package declared by composer inventory policy or remove the component from APP_RUNTIME_SCOPE/runtime lock.',
                    blocksMutations: true,
                    context: $row->toArray(),
                );
            }

            if ('disabled_by_lock' === $row->status) {
                $items[] = new AdministrationConnectedComponentRemediationItem(
                    component: $row->component,
                    key: $row->component.'.disabled_by_lock',
                    severity: 'medium',
                    title: 'Component is disabled by runtime-scope lock.',
                    recommendation: 'Regenerate config/kernel/runtime_scope.*lock.php after deciding whether the component belongs to this host.',
                    blocksMutations: false,
                    context: $row->toArray(),
                );
            }
        }

        return new AdministrationConnectedComponentRemediationPlan(new \DateTimeImmutable(), $items, [
            'Keep foreign component classes out of Administering service definitions.',
            'Use APP_RUNTIME_SCOPE, composer inventory, and runtime-scope locks as the decision surface.',
        ]);
    }
}
