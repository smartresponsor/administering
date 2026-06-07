<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Rolling;

use App\Administering\Value\Rolling\AdministrationRollingSubjectAccessReport;

interface AdministrationRollingSubjectAccessReportProviderInterface
{
    public function reportFor(string $subjectIdentifier, string $scope): AdministrationRollingSubjectAccessReport;
}
