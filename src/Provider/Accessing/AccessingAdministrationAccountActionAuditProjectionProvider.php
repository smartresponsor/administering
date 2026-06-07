<?php

declare(strict_types=1);

namespace App\Administering\Provider\Accessing;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionAuditProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditProjection;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditReport;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditSummary;

/**
 * Self-contained Administering fallback when the Accessing component is not installed.
 */
final readonly class AccessingAdministrationAccountActionAuditProjectionProvider implements AdministrationAccountActionAuditProjectionProviderInterface
{
    /** @return list<AdministrationAccountActionAuditProjection> */
    public function recent(int $limit = 50): array
    {
        return [];
    }

    public function summary(int $limit = 200): AdministrationAccountActionAuditSummary
    {
        return new AdministrationAccountActionAuditSummary(0);
    }

    public function filteredReport(?string $action = null, ?string $status = null, ?string $accountReference = null, int $limit = 100): AdministrationAccountActionAuditReport
    {
        return new AdministrationAccountActionAuditReport([
            'action' => $action,
            'status' => $status,
            'account_reference' => $accountReference,
            'limit' => $limit,
        ], new AdministrationAccountActionAuditSummary(0), []);
    }
}
