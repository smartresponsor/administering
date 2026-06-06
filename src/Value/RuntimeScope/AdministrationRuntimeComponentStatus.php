<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeComponentStatus
{
    /**
     * @param array<string, bool|string|null> $evidence
     */
    public function __construct(
        public string $componentKey,
        public bool $inRuntimeScope,
        public bool $composerPackageInstalled,
        public bool $lockEnabled,
        public bool $lockDisabled,
        public string $status,
        public array $evidence,
    ) {
    }

    /** @return array<string, bool|string|null> */
    public function toArray(): array
    {
        return [
            'componentKey' => $this->componentKey,
            'inRuntimeScope' => $this->inRuntimeScope,
            'composerPackageInstalled' => $this->composerPackageInstalled,
            'lockEnabled' => $this->lockEnabled,
            'lockDisabled' => $this->lockDisabled,
            'status' => $this->status,
            'runtimeScope' => $this->evidence['runtimeScope'] ?? null,
            'composerPackage' => $this->evidence['composerPackage'] ?? null,
            'bundleToken' => $this->evidence['bundleToken'] ?? null,
        ];
    }
}
