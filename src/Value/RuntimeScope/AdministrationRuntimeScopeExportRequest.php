<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeScopeExportRequest
{
    /**
     * @param list<string> $forceEnable
     * @param list<string> $forceDisable
     */
    public function __construct(
        public string $hostDir,
        public string $environment,
        public string $scope,
        public string $catalogFile,
        public bool $strict,
        public bool $skipMissingPackages,
        public bool $dryRun,
        public array $forceEnable,
        public array $forceDisable,
    ) {
    }
}
