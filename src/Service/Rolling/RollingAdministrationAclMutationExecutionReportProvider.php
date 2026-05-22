<?php

declare(strict_types=1);

namespace App\Administering\Service\Rolling;

use App\Administering\ServiceInterface\Rolling\AdministrationAclMutationExecutionReportProviderInterface;
use App\Administering\Value\Rolling\AdministrationAclMutationExecutionReport;
use App\Rolling\ServiceInterface\Administration\RollingAclMutationExecutionReportProviderInterface;
use App\Rolling\Value\Administration\RollingAclMutationExecutionFilter;

/**
 * Maps Rolling-owned ACL execution reports to Administering-owned safe values.
 */
final readonly class RollingAdministrationAclMutationExecutionReportProvider implements AdministrationAclMutationExecutionReportProviderInterface
{
    public function __construct(private RollingAclMutationExecutionReportProviderInterface $provider)
    {
    }

    public function report(?string $mutationType = null, ?string $status = null, ?string $subjectIdentifier = null, int $limit = 100): AdministrationAclMutationExecutionReport
    {
        $report = $this->provider->report(new RollingAclMutationExecutionFilter(
            '' !== $mutationType ? $mutationType : null,
            '' !== $status ? $status : null,
            '' !== $subjectIdentifier ? $subjectIdentifier : null,
            $limit,
        ));

        return new AdministrationAclMutationExecutionReport(
            $report->filter()->toSafeArray(),
            $report->summary()->toSafeArray(),
            array_map(
                static fn ($event): array => $event->toSafeArray(),
                $report->events(),
            ),
        );
    }
}
