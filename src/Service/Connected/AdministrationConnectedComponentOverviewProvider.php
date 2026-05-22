<?php

declare(strict_types=1);

namespace App\Administering\Service\Connected;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionAuditProjectionProviderInterface;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentOverviewProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationExecutionReportProviderInterface;
use App\Administering\ServiceInterface\Rolling\AdministrationPermissionCatalogProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentOverview;
use App\Administering\Value\Connected\AdministrationConnectedComponentStatus;

/**
 * Builds a safe overview across Accessing and Rolling integration surfaces.
 */
final readonly class AdministrationConnectedComponentOverviewProvider implements AdministrationConnectedComponentOverviewProviderInterface
{
    public function __construct(
        private AdministrationPermissionCatalogProviderInterface $permissionCatalogProvider,
        private AdministrationAccountProjectionProviderInterface $accountProjectionProvider,
        private AdministrationAccountActionAuditProjectionProviderInterface $accountActionAuditProvider,
        private AdministrationAclMutationExecutionReportProviderInterface $aclMutationExecutionReportProvider,
    ) {
    }

    public function overview(): AdministrationConnectedComponentOverview
    {
        $permissions = $this->permissionCatalogProvider->descriptors();
        $accounts = $this->accountProjectionProvider->recent(25);
        $accountAudit = $this->accountActionAuditProvider->filteredReport(limit: 25);
        $aclExecution = $this->aclMutationExecutionReportProvider->report(limit: 25);

        return new AdministrationConnectedComponentOverview(new \DateTimeImmutable(), [
            new AdministrationConnectedComponentStatus('Rolling', 'available', 'Rolling permission catalog is readable.', [
                'permissionCount' => count($permissions),
                'sensitivePermissionCount' => count(array_filter($permissions, static fn ($permission): bool => $permission->sensitive())),
                'categories' => array_values(array_unique(array_map(static fn ($permission): string => $permission->category(), $permissions))),
            ]),
            new AdministrationConnectedComponentStatus('Rolling ACL execution', 'reportable', 'Rolling ACL execution report contract is available.', [
                'summary' => $aclExecution->summary(),
                'filter' => $aclExecution->filter(),
                'eventCount' => count($aclExecution->events()),
            ]),
            new AdministrationConnectedComponentStatus('Accessing', 'available', 'Accessing safe account projection is readable.', [
                'accountProjectionCount' => count($accounts),
            ]),
            new AdministrationConnectedComponentStatus('Accessing account actions', 'auditable', 'Accessing controlled action audit report is readable.', [
                'summary' => $accountAudit->summary()->toSafeArray(),
                'filter' => $accountAudit->filter(),
                'itemCount' => count($accountAudit->items()),
            ]),
        ]);
    }
}
