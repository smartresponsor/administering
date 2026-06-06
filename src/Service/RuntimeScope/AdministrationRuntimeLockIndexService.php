<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex;

final readonly class AdministrationRuntimeLockIndexService
{
    public function __construct(
        private string $projectDir,
        private AdministrationRuntimeScopeLockNormalizer $lockNormalizer,
    ) {
    }

    public function index(string $hostDir = ''): AdministrationRuntimeSourceIndex
    {
        $hostDir = $this->absoluteHostDir($hostDir);
        $defaultLock = $this->lockReport($hostDir, 'config/kernel/runtime_scope.lock.php', 'default');
        $prodLock = $this->lockReport($hostDir, 'config/kernel/runtime_scope.prod.lock.php', 'prod');

        return new AdministrationRuntimeSourceIndex(
            title: 'Runtime scope lock files',
            description: 'Materialized Kernel decisions. APP_ENV=prod uses runtime_scope.prod.lock.php; non-prod uses runtime_scope.lock.php.',
            summaryItems: [
                ['label' => 'Host', 'value' => $hostDir],
                ['label' => 'Default lock status', 'value' => $defaultLock['status']],
                ['label' => 'Production lock status', 'value' => $prodLock['status']],
                ['label' => 'Default enabled bundle tokens', 'value' => (string) count($defaultLock['enabledBundleTokens'])],
                ['label' => 'Production enabled bundle tokens', 'value' => (string) count($prodLock['enabledBundleTokens'])],
            ],
            sections: [
                [
                    'title' => 'runtime_scope.lock.php',
                    'kind' => 'runtime_lock',
                    'rows' => [$defaultLock],
                ],
                [
                    'title' => 'runtime_scope.prod.lock.php',
                    'kind' => 'runtime_lock',
                    'rows' => [$prodLock],
                ],
            ],
            errors: [...$defaultLock['errors'], ...$prodLock['errors']],
        );
    }

    /** @return array{scopeType: string, file: string, path: string, status: string, sha256: ?string, scope: ?string, strict: ?bool, sourceComposerFile: ?string, sourceComposerSha256: ?string, generatedAt: ?string, generatedBy: ?string, enabledBundleTokens: list<string>, enabledComponents: list<string>, disabledComponents: list<string>, errors: list<string>, warnings: list<string>} */
    private function lockReport(string $hostDir, string $relativePath, string $scopeType): array
    {
        $path = rtrim($hostDir, '/\\').'/'.$relativePath;
        $evidence = $this->lockNormalizer->normalize($path);

        return [
            'scopeType' => $scopeType,
            'file' => basename($relativePath),
            'path' => $path,
            'status' => $evidence->status,
            'sha256' => $evidence->sha256,
            'scope' => $evidence->scope,
            'strict' => $evidence->strict,
            'sourceComposerFile' => $evidence->sourceComposerFile,
            'sourceComposerSha256' => $evidence->sourceComposerSha256,
            'generatedAt' => $evidence->generatedAt,
            'generatedBy' => $evidence->generatedBy,
            'enabledBundleTokens' => $evidence->enabledBundleTokens,
            'enabledComponents' => $evidence->enabledComponents,
            'disabledComponents' => $evidence->disabledComponents,
            'errors' => $evidence->errors,
            'warnings' => $evidence->warnings,
        ];
    }

    private function absoluteHostDir(string $hostDir): string
    {
        $hostDir = trim($hostDir);
        if ('' === $hostDir) {
            return rtrim($this->projectDir, '/\\');
        }

        if (str_starts_with($hostDir, '/') || preg_match('/^[A-Za-z]:[\\/]/', $hostDir)) {
            return rtrim($hostDir, '/\\');
        }

        return rtrim($this->projectDir, '/\\').'/'.trim($hostDir, '/\\');
    }
}
