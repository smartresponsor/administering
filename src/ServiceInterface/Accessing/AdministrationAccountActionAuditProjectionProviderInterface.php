<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Accessing;

use App\Administering\Value\Accessing\AdministrationAccountActionAuditProjection;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditReport;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditSummary;

/**
 * Provides Administering-safe projections of Accessing controlled action audit.
 */
interface AdministrationAccountActionAuditProjectionProviderInterface
{
    /** @return list<AdministrationAccountActionAuditProjection> */
    public function recent(int $limit = 50): array;

    public function summary(int $limit = 200): AdministrationAccountActionAuditSummary;

    public function filteredReport(?string $action = null, ?string $status = null, ?string $accountReference = null, int $limit = 100): AdministrationAccountActionAuditReport;
}
