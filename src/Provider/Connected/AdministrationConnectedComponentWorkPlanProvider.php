<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationWorkPlanProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentWorkPlanProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentWorkItem;
use App\Administering\Value\Connected\AdministrationConnectedComponentWorkPlan;
use App\Rolling\ServiceInterface\Administration\RollingAclWorkPlanProviderInterface;

/**
 * Aggregates machine-readable, metadata-only work items from connected components.
 */
final readonly class AdministrationConnectedComponentWorkPlanProvider implements AdministrationConnectedComponentWorkPlanProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationWorkPlanProviderInterface $accessingWorkPlanProvider,
        private RollingAclWorkPlanProviderInterface $rollingWorkPlanProvider,
    ) {
    }

    public function plan(): AdministrationConnectedComponentWorkPlan
    {
        $items = [];

        foreach ($this->accessingWorkPlanProvider->plan()->items() as $item) {
            $items[] = new AdministrationConnectedComponentWorkItem(
                'Accessing',
                (string) $item['key'],
                (string) $item['title'],
                (string) $item['stage'],
                (string) $item['priority'],
                (string) $item['actionType'],
                (bool) ($item['blocksMutation'] ?? false),
                is_array($item['dependsOn'] ?? null) ? $item['dependsOn'] : [],
                is_array($item['context'] ?? null) ? $item['context'] : [],
            );
        }

        foreach ($this->rollingWorkPlanProvider->plan()->items() as $item) {
            $items[] = new AdministrationConnectedComponentWorkItem(
                'Rolling',
                (string) $item['key'],
                (string) $item['title'],
                (string) $item['stage'],
                (string) $item['priority'],
                (string) $item['actionType'],
                (bool) ($item['blocksMutation'] ?? false),
                is_array($item['dependsOn'] ?? null) ? $item['dependsOn'] : [],
                is_array($item['context'] ?? null) ? $item['context'] : [],
            );
        }

        return new AdministrationConnectedComponentWorkPlan(
            new \DateTimeImmutable(),
            $items,
            [
                'Use touched-files archives for application; use cumulative archives only for synchronization.',
                'Do not enable irreversible Accessing account mutations until Accessing reports non-bootstrap execution readiness.',
                'Do not enable real Rolling ACL mutations until Rolling reports Doctrine-backed ACL storage readiness.',
                'Never persist plain secrets, password hashes, TOTP secrets, recovery codes, or raw session payloads in Administering.',
            ],
        );
    }
}
