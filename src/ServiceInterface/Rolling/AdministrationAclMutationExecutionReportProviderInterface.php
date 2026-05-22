<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationAclMutationExecutionReport;

/**
 * Provides Administering-safe Rolling ACL execution reports.
 */
interface AdministrationAclMutationExecutionReportProviderInterface
{
    public function report(?string $mutationType = null, ?string $status = null, ?string $subjectIdentifier = null, int $limit = 100): AdministrationAclMutationExecutionReport;
}
