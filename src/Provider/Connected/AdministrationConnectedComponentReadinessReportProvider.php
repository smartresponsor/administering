<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationReadinessReportProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentReadinessReportProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentReadinessReport;
use App\Rolling\ServiceInterface\Administration\RollingAclAdministrationReadinessReportProviderInterface;

/**
 * Bridges Accessing and Rolling readiness reports into one Administering-safe view.
 */
final readonly class AdministrationConnectedComponentReadinessReportProvider implements AdministrationConnectedComponentReadinessReportProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationReadinessReportProviderInterface $accessingReadinessProvider,
        private RollingAclAdministrationReadinessReportProviderInterface $rollingReadinessProvider,
    ) {
    }

    public function report(): AdministrationConnectedComponentReadinessReport
    {
        $accessing = $this->accessingReadinessProvider->report(100)->toSafeArray();
        $rolling = $this->rollingReadinessProvider->report()->toSafeArray();
        $warnings = [];

        if (($rolling['storageReadiness']['storageMode'] ?? 'bootstrap') !== 'doctrine') {
            $warnings[] = 'Rolling ACL storage is not yet marked as Doctrine-backed; mutation execution may still be bootstrap/rejecting.';
        }

        if (($accessing['executionReadiness']['executionMode'] ?? 'bootstrap') !== 'doctrine') {
            $warnings[] = 'Accessing account actions are not yet marked as fully persistent execution actions.';
        }

        return new AdministrationConnectedComponentReadinessReport(
            new \DateTimeImmutable(),
            $accessing,
            $rolling,
            $warnings,
        );
    }
}
