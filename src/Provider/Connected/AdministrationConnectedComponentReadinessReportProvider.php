<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Administering\Service\Connected\AdministrationRuntimeScopeConnectedComponentProjectionService;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentReadinessReportProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentReadinessReport;

/** Reports connected-component readiness from APP_RUNTIME_SCOPE, composer, and lock evidence only. */
final readonly class AdministrationConnectedComponentReadinessReportProvider implements AdministrationConnectedComponentReadinessReportProviderInterface
{
    public function __construct(private AdministrationRuntimeScopeConnectedComponentProjectionService $projection)
    {
    }

    public function report(): AdministrationConnectedComponentReadinessReport
    {
        return new AdministrationConnectedComponentReadinessReport(
            generatedAt: new \DateTimeImmutable(),
            components: $this->rowsByComponent(),
            warnings: $this->projection->sourceErrors(),
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function rowsByComponent(): array
    {
        $rows = [];
        foreach ($this->projection->decisionRows() as $row) {
            $rows[$row->component] = $row->toArray();
        }

        return $rows;
    }
}
