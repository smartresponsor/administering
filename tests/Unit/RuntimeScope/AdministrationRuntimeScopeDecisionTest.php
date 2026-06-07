<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeComponentStatusService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecision;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeState;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeDecisionTest extends TestCase
{
    public function testComponentRowsExposeStableReportBooleansAndReason(): void
    {
        $state = new AdministrationRuntimeScopeState(
            hostDir: '/tmp/administering-host',
            environment: 'prod',
            composerFile: 'composer.prod.json',
            composerPath: '/tmp/administering-host/composer.prod.json',
            composerPackages: ['cruding/crud' => true],
            composerComponentPackages: ['cruding' => 'cruding/crud'],
            appRuntimeScopeRaw: 'cruding,viewing',
            appRuntimeScope: ['cruding', 'viewing'],
            lockPath: '/tmp/administering-host/config/kernel/runtime_scope.prod.lock.php',
            lockPresent: true,
            enabledBundleTokens: ['cruding.bundle'],
            enabledComponents: ['cruding'],
            disabledComponents: [],
            installedComponents: ['cruding'],
            sourceErrors: [],
        );

        $decision = new AdministrationRuntimeScopeDecision(
            state: $state,
            componentStatuses: (new AdministrationRuntimeComponentStatusService())->statuses($state),
        );

        $rows = $this->rowsByComponent($decision->componentRows());

        self::assertSame('available', $rows['cruding']['status']);
        self::assertTrue($rows['cruding']['present']);
        self::assertTrue($rows['cruding']['allowed']);
        self::assertTrue($rows['cruding']['locked']);
        self::assertTrue($rows['cruding']['enabled']);
        self::assertFalse($rows['cruding']['disabled']);
        self::assertSame('Component is inside APP_RUNTIME_SCOPE and enabled by runtime lock evidence.', $rows['cruding']['reason']);

        self::assertSame('missing_package', $rows['viewing']['status']);
        self::assertFalse($rows['viewing']['present']);
        self::assertTrue($rows['viewing']['allowed']);
        self::assertFalse($rows['viewing']['locked']);
        self::assertFalse($rows['viewing']['enabled']);
        self::assertSame('Component is requested by APP_RUNTIME_SCOPE but composer inventory does not contain its package.', $rows['viewing']['reason']);
    }

    public function testDecisionRowsExposeNormalizedObjectAndArrayContracts(): void
    {
        $state = new AdministrationRuntimeScopeState(
            hostDir: '/tmp/administering-host',
            environment: 'prod',
            composerFile: 'composer.prod.json',
            composerPath: '/tmp/administering-host/composer.prod.json',
            composerPackages: ['viewing/view' => true],
            composerComponentPackages: ['viewing' => 'viewing/view'],
            appRuntimeScopeRaw: 'viewing',
            appRuntimeScope: ['viewing'],
            lockPath: '/tmp/administering-host/config/kernel/runtime_scope.prod.lock.php',
            lockPresent: true,
            enabledBundleTokens: ['viewing.bundle'],
            enabledComponents: ['viewing'],
            disabledComponents: [],
            installedComponents: ['viewing'],
            sourceErrors: [],
        );

        $decision = new AdministrationRuntimeScopeDecision(
            state: $state,
            componentStatuses: (new AdministrationRuntimeComponentStatusService())->statuses($state),
        );

        $rows = $decision->decisionRowsByComponent();
        self::assertArrayHasKey('viewing', $rows);
        self::assertTrue($rows['viewing']->present);
        self::assertTrue($rows['viewing']->allowed);
        self::assertTrue($rows['viewing']->locked);
        self::assertTrue($rows['viewing']->enabled);
        self::assertFalse($rows['viewing']->disabled);

        $componentRows = $this->rowsByComponent($decision->componentRows());
        self::assertSame($rows['viewing']->toArray(), $componentRows['viewing']);
        self::assertSame('viewing.bundle', $rows['viewing']->evidenceContext()['bundleToken']);
    }

    public function testSourceSummaryIsStableForReportCommand(): void
    {
        $state = new AdministrationRuntimeScopeState(
            hostDir: '/tmp/administering-host',
            environment: 'dev',
            composerFile: 'composer.json',
            composerPath: '/tmp/administering-host/composer.json',
            composerPackages: ['administering/admin' => true],
            composerComponentPackages: ['administering' => 'administering/admin'],
            appRuntimeScopeRaw: '',
            appRuntimeScope: ['administering'],
            lockPath: '/tmp/administering-host/config/kernel/runtime_scope.lock.php',
            lockPresent: false,
            enabledBundleTokens: [],
            enabledComponents: [],
            disabledComponents: [],
            installedComponents: ['administering'],
            sourceErrors: ['Runtime scope lock is missing.'],
        );

        $decision = new AdministrationRuntimeScopeDecision(
            state: $state,
            componentStatuses: [],
        );

        self::assertSame([
            'hostDir' => '/tmp/administering-host',
            'environment' => 'dev',
            'appRuntimeScope' => '',
            'appRuntimeScopeTokens' => ['administering'],
            'composerFile' => 'composer.json',
            'composerPath' => '/tmp/administering-host/composer.json',
            'composerPackageCount' => 1,
            'lockPath' => '/tmp/administering-host/config/kernel/runtime_scope.lock.php',
            'lockPresent' => false,
            'enabledBundleTokenCount' => 0,
            'enabledComponentCount' => 0,
            'disabledComponentCount' => 0,
            'installedComponentCount' => 1,
            'sourceErrors' => ['Runtime scope lock is missing.'],
        ], $decision->sourceSummary());
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, array<string, mixed>>
     */
    private function rowsByComponent(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(string) $row['component']] = $row;
        }

        return $indexed;
    }
}
