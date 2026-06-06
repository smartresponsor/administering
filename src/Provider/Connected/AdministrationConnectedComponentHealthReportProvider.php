<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentHealthReportProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentHealthCheck;
use App\Administering\Value\Connected\AdministrationConnectedComponentHealthReport;

/** Reports health using local runtime evidence only. */
final readonly class AdministrationConnectedComponentHealthReportProvider implements AdministrationConnectedComponentHealthReportProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function report(): AdministrationConnectedComponentHealthReport
    {
        $checks = [];
        foreach ($this->projection->decisionRows() as $row) {
            $checks[] = new AdministrationConnectedComponentHealthCheck(
                component: $row->component,
                key: $row->component.'.runtime_scope.health',
                label: $row->component.' runtime-scope health',
                category: 'runtime_scope',
                status: in_array($row->status, ['available', 'package_installed'], true) ? 'ok' : 'warning',
                severity: in_array($row->status, ['missing_package', 'disabled_by_lock'], true) ? 'high' : 'info',
                blocking: 'missing_package' === $row->status,
                context: $row->toArray(),
            );
        }

        foreach ($this->projection->sourceErrors() as $index => $error) {
            $checks[] = new AdministrationConnectedComponentHealthCheck(
                component: 'administering',
                key: 'runtime_scope.source_error.'.($index + 1),
                label: 'Runtime-scope source error',
                category: 'runtime_scope_source',
                status: 'warning',
                severity: 'medium',
                blocking: false,
                context: ['message' => $error],
            );
        }

        return new AdministrationConnectedComponentHealthReport(new \DateTimeImmutable(), $checks, $this->guards());
    }

    /** @return list<string> */
    private function guards(): array
    {
        return ['Health is read-only and evidence-only; no foreign container services are required.'];
    }
}
