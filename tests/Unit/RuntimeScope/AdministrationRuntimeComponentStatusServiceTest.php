<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeComponentStatusService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeComponentStatus;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeState;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeComponentStatusServiceTest extends TestCase
{
    private AdministrationRuntimeComponentStatusService $service;

    protected function setUp(): void
    {
        $this->service = new AdministrationRuntimeComponentStatusService();
    }

    public function testEmptyRuntimeScopeKeepsStandaloneAdministeringAvailable(): void
    {
        $rows = $this->statusesByComponent($this->state(runtimeScope: null));

        self::assertArrayHasKey('administering', $rows);
        self::assertSame('available', $rows['administering']->status);
        self::assertTrue($rows['administering']->inRuntimeScope);
        self::assertTrue($rows['administering']->composerPackageInstalled);
        self::assertTrue($rows['administering']->lockEnabled);
    }

    public function testRequestedInstalledAndLockedComponentIsAvailable(): void
    {
        $rows = $this->statusesByComponent($this->state(
            runtimeScope: 'accessing',
            composerPackages: ['accessing/access' => true],
            installedComponents: ['accessing'],
            enabledBundleTokens: ['accessing.bundle'],
            enabledComponents: ['accessing'],
        ));

        self::assertSame('available', $rows['accessing']->status);
        self::assertTrue($rows['accessing']->inRuntimeScope);
        self::assertTrue($rows['accessing']->composerPackageInstalled);
        self::assertTrue($rows['accessing']->lockEnabled);
        self::assertFalse($rows['accessing']->lockDisabled);
    }

    public function testRequestedMissingComponentReportsMissingPackage(): void
    {
        $rows = $this->statusesByComponent($this->state(runtimeScope: 'rolling'));

        self::assertSame('missing_package', $rows['rolling']->status);
        self::assertTrue($rows['rolling']->inRuntimeScope);
        self::assertFalse($rows['rolling']->composerPackageInstalled);
        self::assertFalse($rows['rolling']->lockEnabled);
    }

    public function testInstalledComponentOutsideScopeReportsOutOfScope(): void
    {
        $rows = $this->statusesByComponent($this->state(
            runtimeScope: 'administering',
            composerPackages: ['managing/manage' => true],
            installedComponents: ['managing'],
            enabledBundleTokens: ['managing.bundle'],
            enabledComponents: ['managing'],
        ));

        self::assertSame('out_of_scope', $rows['managing']->status);
        self::assertFalse($rows['managing']->inRuntimeScope);
        self::assertTrue($rows['managing']->composerPackageInstalled);
        self::assertTrue($rows['managing']->lockEnabled);
    }

    public function testDisabledLockOverridesInstalledAndRequestedComponent(): void
    {
        $rows = $this->statusesByComponent($this->state(
            runtimeScope: 'viewing',
            composerPackages: ['viewing/view' => true],
            installedComponents: ['viewing'],
            enabledBundleTokens: ['viewing.bundle'],
            enabledComponents: ['viewing'],
            disabledComponents: ['viewing'],
        ));

        self::assertSame('disabled_by_lock', $rows['viewing']->status);
        self::assertTrue($rows['viewing']->inRuntimeScope);
        self::assertTrue($rows['viewing']->composerPackageInstalled);
        self::assertTrue($rows['viewing']->lockEnabled);
        self::assertTrue($rows['viewing']->lockDisabled);
    }

    /**
     * @param array<string, true> $composerPackages
     * @param list<string>        $installedComponents
     * @param list<string>        $enabledBundleTokens
     * @param list<string>        $enabledComponents
     * @param list<string>        $disabledComponents
     */
    private function state(
        ?string $runtimeScope,
        array $composerPackages = [],
        array $installedComponents = [],
        array $enabledBundleTokens = [],
        array $enabledComponents = [],
        array $disabledComponents = [],
    ): AdministrationRuntimeScopeState {
        return new AdministrationRuntimeScopeState(
            hostDir: '/tmp/administering-host',
            environment: 'test',
            composerFile: 'composer.json',
            composerPath: '/tmp/administering-host/composer.json',
            composerPackages: $composerPackages,
            composerComponentPackages: [],
            appRuntimeScopeRaw: $runtimeScope,
            appRuntimeScope: null === $runtimeScope ? ['administering'] : explode(',', $runtimeScope),
            lockPath: '/tmp/administering-host/config/kernel/runtime_scope.lock.php',
            lockPresent: [] !== $enabledComponents,
            enabledBundleTokens: $enabledBundleTokens,
            enabledComponents: $enabledComponents,
            disabledComponents: $disabledComponents,
            installedComponents: $installedComponents,
            sourceErrors: [],
        );
    }

    /**
     * @return array<string, AdministrationRuntimeComponentStatus>
     */
    private function statusesByComponent(AdministrationRuntimeScopeState $state): array
    {
        $rows = [];
        foreach ($this->service->statuses($state) as $status) {
            $rows[$status->componentKey] = $status;
        }

        return $rows;
    }
}
