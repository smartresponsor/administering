<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentDiagnosticReportProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentDiagnosticIssue;
use App\Administering\Value\Connected\AdministrationConnectedComponentDiagnosticReport;

/** Builds diagnostics from local evidence instead of foreign diagnostics providers. */
final readonly class AdministrationConnectedComponentDiagnosticReportProvider implements AdministrationConnectedComponentDiagnosticReportProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function report(): AdministrationConnectedComponentDiagnosticReport
    {
        $issues = [];
        foreach ($this->projection->decisionRows() as $row) {
            if (in_array($row->status, ['available', 'package_installed'], true)) {
                continue;
            }

            $issues[] = new AdministrationConnectedComponentDiagnosticIssue(
                component: $row->component,
                key: $row->component.'.'.$row->status,
                label: $row->message,
                category: 'runtime_scope',
                severity: 'missing_package' === $row->status ? 'high' : 'medium',
                status: $row->status,
                blocking: 'missing_package' === $row->status,
                context: $row->toArray(),
            );
        }

        foreach ($this->projection->sourceErrors() as $index => $error) {
            $issues[] = new AdministrationConnectedComponentDiagnosticIssue(
                component: 'administering',
                key: 'runtime_scope.source_error.'.($index + 1),
                label: $error,
                category: 'runtime_scope_source',
                severity: 'medium',
                status: 'source_warning',
                blocking: false,
                context: ['message' => $error],
            );
        }

        return new AdministrationConnectedComponentDiagnosticReport(new \DateTimeImmutable(), $issues, $this->guards());
    }

    /** @return list<string> */
    private function guards(): array
    {
        return ['Diagnostics are derived from local runtime-scope sources only.'];
    }
}
