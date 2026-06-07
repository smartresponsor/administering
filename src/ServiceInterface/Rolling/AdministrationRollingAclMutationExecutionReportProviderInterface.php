<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationRollingAclMutationExecutionReport;

interface AdministrationRollingAclMutationExecutionReportProviderInterface
{
    public function report(?string $mutationType, ?string $status, ?string $subjectIdentifier, int $limit = 100): AdministrationRollingAclMutationExecutionReport;
}
