<?php

declare(strict_types=1);

namespace App\Administering\Provider\Accessing;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditProjection;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountActionAuditProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditProjection;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditReport;
use App\Administering\Value\Accessing\AdministrationAccountActionAuditSummary;

/**
 * Maps Accessing-owned audit projections to Administering-owned UI values.
 */
final readonly class AdministrationAccessingAccountActionAuditProjectionProvider implements AdministrationAccountActionAuditProjectionProviderInterface
{
    public function __construct(private AccessAccountAdministrationAuditProjectionProviderInterface $provider)
    {
    }

    /** @return list<AdministrationAccountActionAuditProjection> */
    public function recent(int $limit = 50): array
    {
        return array_map(
            static fn (AccessAccountAdministrationAuditProjection $projection): AdministrationAccountActionAuditProjection => new AdministrationAccountActionAuditProjection(
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
        $filter = new AccessAccountAdministrationAuditFilter(
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
                static fn (AccessAccountAdministrationAuditProjection $projection): AdministrationAccountActionAuditProjection => new AdministrationAccountActionAuditProjection(
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
