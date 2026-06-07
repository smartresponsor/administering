<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationRollingAclMutationExecutionReportProviderInterface;
use App\Administering\Value\Rolling\AdministrationRollingAclMutationExecutionReport;

final readonly class AdministrationDryRollingAclMutationExecutionReportProvider implements AdministrationRollingAclMutationExecutionReportProviderInterface
{
    public function report(?string $mutationType, ?string $status, ?string $subjectIdentifier, int $limit = 100): AdministrationRollingAclMutationExecutionReport
    {
        return new AdministrationRollingAclMutationExecutionReport([], [
            'mutation_type' => $mutationType,
            'status' => $status,
            'subject_identifier' => $subjectIdentifier,
            'limit' => max(1, min(500, $limit)),
            'mode' => 'administering_self_contained_dry_runtime',
        ]);
    }
}
