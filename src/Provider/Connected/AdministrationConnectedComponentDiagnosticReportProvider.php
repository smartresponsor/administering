<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationDiagnosticReportProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentDiagnosticReportProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentDiagnosticIssue;
use App\Administering\Value\Connected\AdministrationConnectedComponentDiagnosticReport;
use App\Rolling\ServiceInterface\Administration\RollingAclDiagnosticReportProviderInterface;

/**
 * Aggregates safe diagnostic issue registers from connected administration components.
 */
final readonly class AdministrationConnectedComponentDiagnosticReportProvider implements AdministrationConnectedComponentDiagnosticReportProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationDiagnosticReportProviderInterface $accessingDiagnosticsProvider,
        private RollingAclDiagnosticReportProviderInterface $rollingDiagnosticsProvider,
    ) {
    }

    public function report(): AdministrationConnectedComponentDiagnosticReport
    {
        $issues = [];

        foreach ($this->accessingDiagnosticsProvider->report()->issues() as $issue) {
            $issues[] = $this->mapIssue('Accessing', $issue->toSafeArray());
        }

        foreach ($this->rollingDiagnosticsProvider->report()->issues() as $issue) {
            $issues[] = $this->mapIssue('Rolling', $issue->toSafeArray());
        }

        return new AdministrationConnectedComponentDiagnosticReport(
            new \DateTimeImmutable(),
            $issues,
            [
                'This diagnostic register is metadata-only and does not execute account or ACL mutations.',
                'Accessing diagnostics must not expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
                'Rolling diagnostics must not expose raw subject grants, raw policy internals, sessions, passwords, or secrets.',
                'Blocking issues should keep Administering mutation screens in review/disabled mode until the owning component is ready.',
            ],
        );
    }

    /** @param array<string, mixed> $issue */
    private function mapIssue(string $component, array $issue): AdministrationConnectedComponentDiagnosticIssue
    {
        return new AdministrationConnectedComponentDiagnosticIssue(
            $component,
            (string) $issue['key'],
            (string) $issue['label'],
            (string) $issue['category'],
            (string) $issue['severity'],
            (string) $issue['status'],
            (bool) ($issue['blocking'] ?? false),
            is_array($issue['context'] ?? null) ? $issue['context'] : [],
        );
    }
}
