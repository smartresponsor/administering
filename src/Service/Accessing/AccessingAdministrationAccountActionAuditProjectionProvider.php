<?php

declare(strict_types=1);

namespace App\Administering\Service\Accessing;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationAuditProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditProjection;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionAuditProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditProjection;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditReport;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditSummary;

/**
 * Maps Accessing-owned audit projections to Administering-owned UI values.
 */
final readonly class AccessingAdministrationAccountActionAuditProjectionProvider implements AdministrationAccountActionAuditProjectionProviderInterface
{
    public function __construct(private AccessingAccountAdministrationAuditProjectionProviderInterface $provider)
    {
    }

    /** @return list<AdministrationAccountActionAuditProjection> */
    public function recent(int $limit = 50): array
    {
        return array_map(
            static fn (AccessingAccountAdministrationAuditProjection $projection): AdministrationAccountActionAuditProjection => new AdministrationAccountActionAuditProjection(
                $projection->action(),
                $projection->accountReference(),
                $projection->requestedBySubject(),
                $projection->resultStatus(),
                $projection->safeMessage(),
                $projection->createdAt(),
                $projection->safeContext(),
            ),
            $this->provider->recent($limit),
        );
    }

    public function summary(int $limit = 200): AdministrationAccountActionAuditSummary
    {
        $summary = $this->provider->summary($limit);

        return new AdministrationAccountActionAuditSummary(
            $summary->total(),
            $summary->countByStatus(),
            $summary->countByAction(),
            $summary->latestAt(),
        );
    }

    public function filteredReport(?string $action = null, ?string $status = null, ?string $accountReference = null, int $limit = 100): AdministrationAccountActionAuditReport
    {
        $filter = new AccessingAccountAdministrationAuditFilter(
            '' !== $action ? $action : null,
            '' !== $status ? $status : null,
            '' !== $accountReference ? $accountReference : null,
            $limit,
        );
        $report = $this->provider->report($filter);
        $summary = $report->summary();

        return new AdministrationAccountActionAuditReport(
            $report->filter()->toSafeArray(),
            new AdministrationAccountActionAuditSummary(
                $summary->total(),
                $summary->countByStatus(),
                $summary->countByAction(),
                $summary->latestAt(),
            ),
            array_map(
                static fn (AccessingAccountAdministrationAuditProjection $projection): AdministrationAccountActionAuditProjection => new AdministrationAccountActionAuditProjection(
                    $projection->action(),
                    $projection->accountReference(),
                    $projection->requestedBySubject(),
                    $projection->resultStatus(),
                    $projection->safeMessage(),
                    $projection->createdAt(),
                    $projection->safeContext(),
                ),
                $report->items(),
            ),
        );
    }
}
