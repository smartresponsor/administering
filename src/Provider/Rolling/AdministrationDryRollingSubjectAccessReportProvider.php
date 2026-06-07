<?php

declare(strict_types=1);

namespace App\Administering\Provider\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationRollingSubjectAccessReportProviderInterface;
use App\Administering\Value\Rolling\AdministrationRollingSubjectAccessReport;

final readonly class AdministrationDryRollingSubjectAccessReportProvider implements AdministrationRollingSubjectAccessReportProviderInterface
{
    public function reportFor(string $subjectIdentifier, string $scope): AdministrationRollingSubjectAccessReport
    {
        return new AdministrationRollingSubjectAccessReport($subjectIdentifier, $scope);
    }
}
