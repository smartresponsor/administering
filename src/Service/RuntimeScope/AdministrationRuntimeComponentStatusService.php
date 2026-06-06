<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeComponentStatus;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeState;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeVisibility;

final readonly class AdministrationRuntimeComponentStatusService
{
    /**
     * @return list<AdministrationRuntimeComponentStatus>
     */
    public function statuses(AdministrationRuntimeScopeState $state): array
    {
        $visibility = AdministrationRuntimeScopeVisibility::fromRaw($state->appRuntimeScopeRaw);
        $componentKeys = array_values(array_unique(array_filter(array_merge(
            ['administering'],
            $visibility->allComponentsVisible ? [] : $visibility->componentKeys,
            $state->installedComponents,
            $state->enabledComponents,
            $state->disabledComponents,
        ))));
        sort($componentKeys);

        $statuses = [];
        foreach ($componentKeys as $componentKey) {
            $statuses[] = $this->statusFor($componentKey, $visibility, $state);
        }

        return $statuses;
    }

    private function statusFor(
        string $componentKey,
        AdministrationRuntimeScopeVisibility $visibility,
        AdministrationRuntimeScopeState $state,
    ): AdministrationRuntimeComponentStatus {
        $inRuntimeScope = $visibility->includes($componentKey);
        $packageInstalled = in_array($componentKey, $state->installedComponents, true) || 'administering' === $componentKey;
        $lockEnabled = in_array($componentKey, $state->enabledComponents, true) || 'administering' === $componentKey;
        $lockDisabled = in_array($componentKey, $state->disabledComponents, true);

        $status = match (true) {
            !$inRuntimeScope => 'out_of_scope',
            $lockDisabled => 'disabled_by_lock',
            !$packageInstalled => 'missing_package',
            $lockEnabled => 'available',
            default => 'package_installed',
        };

        return new AdministrationRuntimeComponentStatus(
            componentKey: $componentKey,
            inRuntimeScope: $inRuntimeScope,
            composerPackageInstalled: $packageInstalled,
            lockEnabled: $lockEnabled,
            lockDisabled: $lockDisabled,
            status: $status,
            evidence: [
                'runtimeScope' => $visibility->label(),
                'composerPackage' => $this->packageForComponent($componentKey, $state),
                'bundleToken' => $this->bundleTokenForComponent($componentKey, $state),
            ],
        );
    }

    private function packageForComponent(string $componentKey, AdministrationRuntimeScopeState $state): ?string
    {
        if ('administering' === $componentKey) {
            return $state->composerComponentPackages['administering'] ?? 'administering/administering';
        }

        return $state->composerComponentPackages[$componentKey] ?? null;
    }

    private function bundleTokenForComponent(string $componentKey, AdministrationRuntimeScopeState $state): ?string
    {
        foreach ($state->enabledBundleTokens as $bundleToken) {
            if ($this->componentFromBundleToken($bundleToken) === $componentKey) {
                return $bundleToken;
            }
        }

        return 'administering' === $componentKey ? 'administering.bundle' : null;
    }

    private function componentFromBundleToken(string $bundleToken): string
    {
        return AdministrationRuntimeScopeVisibility::normalizeComponent(str_replace('.bundle', '', $bundleToken));
    }
}
