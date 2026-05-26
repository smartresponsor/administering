<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationHealthReportProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentHealthReportProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentHealthCheck;
use App\Administering\Value\Connected\AdministrationConnectedComponentHealthReport;
use App\Rolling\ServiceInterface\Administration\RollingAclHealthReportProviderInterface;

/**
 * Aggregates safe health reports from connected administration components.
 */
final readonly class AdministrationConnectedComponentHealthReportProvider implements AdministrationConnectedComponentHealthReportProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationHealthReportProviderInterface $accessingHealthProvider,
        private RollingAclHealthReportProviderInterface $rollingHealthProvider,
    ) {
    }

    public function report(): AdministrationConnectedComponentHealthReport
    {
        $checks = [];

        foreach ($this->accessingHealthProvider->report()->checks() as $check) {
            $checks[] = $this->mapCheck('Accessing', $check->toSafeArray());
        }

        foreach ($this->rollingHealthProvider->report()->checks() as $check) {
            $checks[] = $this->mapCheck('Rolling', $check->toSafeArray());
        }

        return new AdministrationConnectedComponentHealthReport(
            new \DateTimeImmutable(),
            $checks,
            [
                'This report is metadata-only and is not an executor.',
                'Accessing health checks must not expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
                'Rolling health checks must not expose raw subject grants, raw policy internals, sessions, passwords, or secrets.',
                'Blocking checks indicate that Administering should keep real mutations behind review or disabled until the owning component is ready.',
            ],
        );
    }

    /** @param array<string, mixed> $check */
    private function mapCheck(string $component, array $check): AdministrationConnectedComponentHealthCheck
    {
        return new AdministrationConnectedComponentHealthCheck(
            $component,
            (string) $check['key'],
            (string) $check['label'],
            (string) $check['category'],
            (string) $check['status'],
            (string) $check['severity'],
            (bool) ($check['blocking'] ?? false),
            is_array($check['context'] ?? null) ? $check['context'] : [],
        );
    }
}
