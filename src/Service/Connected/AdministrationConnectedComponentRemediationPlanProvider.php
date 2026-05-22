<?php

declare(strict_types=1);

namespace App\Administering\Service\Connected;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationRemediationPlanProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentRemediationPlanProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentRemediationItem;
use App\Administering\Value\Connected\AdministrationConnectedComponentRemediationPlan;
use App\Rolling\ServiceInterface\Administration\RollingAclRemediationPlanProviderInterface;

/**
 * Aggregates connected-component remediation hints without exposing credentials or raw security internals.
 */
final readonly class AdministrationConnectedComponentRemediationPlanProvider implements AdministrationConnectedComponentRemediationPlanProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationRemediationPlanProviderInterface $accessingProvider,
        private RollingAclRemediationPlanProviderInterface $rollingProvider,
    ) {
    }

    public function plan(): AdministrationConnectedComponentRemediationPlan
    {
        $items = [];

        foreach ($this->accessingProvider->plan()->items() as $item) {
            $items[] = new AdministrationConnectedComponentRemediationItem(
                'Accessing',
                (string) $item['key'],
                (string) $item['severity'],
                (string) $item['title'],
                (string) $item['recommendation'],
                (bool) ($item['blocksMutations'] ?? false),
                is_array($item['context'] ?? null) ? $item['context'] : [],
            );
        }

        foreach ($this->rollingProvider->plan()->items() as $item) {
            $items[] = new AdministrationConnectedComponentRemediationItem(
                'Rolling',
                (string) $item['key'],
                (string) $item['severity'],
                (string) $item['title'],
                (string) $item['recommendation'],
                (bool) ($item['blocksMutations'] ?? false),
                is_array($item['context'] ?? null) ? $item['context'] : [],
            );
        }

        return new AdministrationConnectedComponentRemediationPlan(
            new \DateTimeImmutable(),
            $items,
            [
                'Keep Accessing as the only authentication/session owner.',
                'Keep Rolling as the ACL/policy decision owner.',
                'Use Administering only as the metadata-safe orchestration and visualization surface.',
            ],
        );
    }
}
