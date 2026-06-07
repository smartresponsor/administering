<?php

declare(strict_types=1);

namespace App\Administering\Tests\Unit\RuntimeScope;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeComponentStatusService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeOutputSchemaService;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecision;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeExportResult;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeState;
use PHPUnit\Framework\TestCase;

final class AdministrationRuntimeScopeOutputSchemaServiceTest extends TestCase
{
    public function testDecisionPayloadUsesNormalizedRuntimeScopeOutputSchema(): void
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

        $payload = (new AdministrationRuntimeScopeOutputSchemaService())->decisionPayload(
            'administration_runtime_scope_capability_index',
            $decision,
        );

        self::assertSame('administering.runtime_scope.output.v1', $payload['schema']);
        self::assertSame('administration_runtime_scope_capability_index', $payload['report']);
        self::assertArrayHasKey('source', $payload);
        self::assertArrayHasKey('components', $payload);
        self::assertArrayHasKey('errors', $payload);
        self::assertArrayHasKey('warnings', $payload);
        $components = [];
        foreach ($payload['components'] as $component) {
            $components[$component['component']] = $component;
        }

        self::assertArrayHasKey('viewing', $components);
        self::assertArrayNotHasKey('componentKey', $components['viewing']);
        self::assertTrue($components['viewing']['present']);
        self::assertTrue($components['viewing']['allowed']);
        self::assertTrue($components['viewing']['locked']);
        self::assertTrue($components['viewing']['enabled']);
    }

    public function testExportPayloadUsesSameTopLevelOutputSchema(): void
    {
        $result = new AdministrationRuntimeScopeExportResult(
            lockPath: '/tmp/administering-host/config/kernel/runtime_scope.prod.lock.php',
            source: '<?php return [];',
            payload: [
                'scope' => 'prod-composer-inventory',
                'environment' => 'prod',
                'sourceComposerFile' => 'composer.prod.json',
                'sourceComposerSha256' => str_repeat('a', 64),
                'sourceComposerPackageCount' => 3,
                'generatedAt' => '2026-06-05T00:00:00+00:00',
                'generatedBy' => 'administering:runtime-scope:export',
                'strict' => true,
                'enabledComponents' => ['viewing'],
                'enabledBundleTokens' => ['viewing.bundle'],
                'disabledComponents' => ['cruding'],
                'skippedComponents' => [],
            ],
        );

        $payload = (new AdministrationRuntimeScopeOutputSchemaService())->exportPayload($result);

        self::assertSame('administering.runtime_scope.output.v1', $payload['schema']);
        self::assertSame('administration_runtime_scope_export', $payload['report']);
        self::assertArrayHasKey('source', $payload);
        self::assertArrayHasKey('components', $payload);
        self::assertArrayHasKey('export', $payload);
        self::assertSame(['viewing.bundle'], $payload['export']['enabledBundleTokens']);

        $rows = [];
        foreach ($payload['components'] as $row) {
            $rows[$row['component']] = $row;
        }

        self::assertTrue($rows['viewing']['enabled']);
        self::assertFalse($rows['cruding']['enabled']);
        self::assertTrue($rows['cruding']['disabled']);
    }
}
