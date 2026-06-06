<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentOverviewProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentOverview;
use App\Administering\Value\Connected\AdministrationConnectedComponentStatus;

/** Builds connected-component overview from runtime evidence only. */
final readonly class AdministrationConnectedComponentOverviewProvider implements AdministrationConnectedComponentOverviewProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function overview(): AdministrationConnectedComponentOverview
    {
        $statuses = [];
        foreach ($this->projection->decisionRows() as $row) {
            $statuses[] = new AdministrationConnectedComponentStatus(
                component: $row->component,
                status: $row->status,
                message: $row->message,
                metadata: [
                    'source' => 'runtime_scope_evidence',
                    'present' => $row->present,
                    'allowed' => $row->allowed,
                    'locked' => $row->locked,
                    'enabled' => $row->enabled,
                    'disabled' => $row->disabled,
                    'runtimeScope' => $row->runtimeScope,
                    'composerPackage' => $row->composerPackage,
                    'bundleToken' => $row->bundleToken,
                ],
            );
        }

        return new AdministrationConnectedComponentOverview(new \DateTimeImmutable(), $statuses);
    }
}
