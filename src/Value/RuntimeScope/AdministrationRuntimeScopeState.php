<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeState
{
    /**
     * @param array<string, true> $composerPackages
     * @param list<string>        $appRuntimeScope
     * @param list<string>        $enabledBundles
     * @param list<string>        $enabledComponents
     * @param list<string>        $disabledComponents
     * @param list<string>        $installedComponents
     * @param list<string>        $sourceErrors
     */
    public function __construct(
        public string $hostDir,
        public string $environment,
        public string $composerFile,
        public string $composerPath,
        public array $composerPackages,
        public ?string $appRuntimeScopeRaw,
        public array $appRuntimeScope,
        public string $lockPath,
        public bool $lockPresent,
        public array $enabledBundles,
        public array $enabledComponents,
        public array $disabledComponents,
        public array $installedComponents,
        public array $sourceErrors,
    ) {
    }
}
