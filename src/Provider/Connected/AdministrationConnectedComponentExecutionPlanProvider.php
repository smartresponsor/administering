<?php

declare(strict_types=1);

namespace App\Administering\Provider\Connected;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionPlanProviderInterface;
use App\Administering\ServiceInterface\Connected\AdministrationConnectedComponentExecutionPlanProviderInterface;
use App\Administering\Value\Connected\AdministrationConnectedComponentExecutionPlan;
use App\Administering\Value\Connected\AdministrationConnectedComponentExecutionStep;
use App\Rolling\ServiceInterface\Administration\RollingAclExecutionPlanProviderInterface;

/**
 * Aggregates safe execution plans from Accessing and Rolling for Administering UI/reporting.
 */
final readonly class AdministrationConnectedComponentExecutionPlanProvider implements AdministrationConnectedComponentExecutionPlanProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationExecutionPlanProviderInterface $accessingExecutionPlanProvider,
        private RollingAclExecutionPlanProviderInterface $rollingExecutionPlanProvider,
    ) {
    }

    public function plan(): AdministrationConnectedComponentExecutionPlan
    {
        $steps = [];

        foreach ($this->accessingExecutionPlanProvider->plan()->steps() as $step) {
            $steps[] = $this->mapStep('Accessing', $step);
        }

        foreach ($this->rollingExecutionPlanProvider->plan()->steps() as $step) {
            $steps[] = $this->mapStep('Rolling', $step);
        }

        return new AdministrationConnectedComponentExecutionPlan(
            new \DateTimeImmutable(),
            $steps,
            [
                'This endpoint is an execution plan, not an executor.',
                'Blocked steps require component-owned implementation before Administering may launch mutations.',
                'Do not serialize secrets, password hashes, TOTP secrets, recovery codes, raw sessions, or raw ACL grants into execution steps.',
                'Real account actions must execute through Accessing; real ACL mutations must execute through Rolling.',
            ],
        );
    }

    /** @param array<string, mixed> $step */
    private function mapStep(string $fallbackComponent, array $step): AdministrationConnectedComponentExecutionStep
    {
        return new AdministrationConnectedComponentExecutionStep(
            (string) ($step['component'] ?? $fallbackComponent),
            (string) $step['key'],
            (string) $step['title'],
            (string) $step['stage'],
            (string) $step['executionType'],
            (bool) ($step['blocked'] ?? true),
            (bool) ($step['requiresReview'] ?? true),
            (bool) ($step['safeToAutomate'] ?? false),
            (string) ($step['sourceWorkItem'] ?? $step['key']),
            is_array($step['context'] ?? null) ? $step['context'] : [],
        );
    }
}
