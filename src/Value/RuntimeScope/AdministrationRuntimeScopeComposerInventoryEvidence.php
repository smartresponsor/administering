<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeComposerInventoryEvidence
{
    /**
     * @param array<string, true>   $packages
     * @param array<string, string> $componentPackages
     * @param list<string>          $ignoredRuntimeScopePackages
     */
    public function __construct(
        public string $composerPath,
        public array $packages,
        public array $componentPackages,
        public array $ignoredRuntimeScopePackages = [],
    ) {
    }

    /** @return list<string> */
    public function installedComponents(): array
    {
        $components = array_keys($this->componentPackages);
        sort($components);

        return $components;
    }

    public function packageForComponent(string $component): ?string
    {
        return $this->componentPackages[AdministrationRuntimeScopeVisibility::normalizeComponent($component)] ?? null;
    }
}
