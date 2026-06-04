<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

final readonly class AdministrationRuntimeScopePathResolver
{
    public function __construct(private string $projectDir)
    {
    }

    public function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\/]/', $path)) {
            return rtrim($path, '/\\');
        }

        return rtrim($this->projectDir, '/\\').'/'.trim($path, '/\\');
    }

    public function composerFile(string $environment): string
    {
        return 'prod' === $environment ? 'composer.prod.json' : 'composer.json';
    }

    public function lockPath(string $hostDir, string $environment): string
    {
        $fileName = 'prod' === $environment ? 'runtime_scope.prod.lock.php' : 'runtime_scope.lock.php';

        return rtrim($hostDir, '/\\').'/config/kernel/'.$fileName;
    }

    public function defaultCatalogFile(): string
    {
        return dirname(__DIR__, 3).'/config/runtime-scope/bundle_catalog.php';
    }
}
