<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex;

final readonly class AdministrationRuntimeLockIndexService
{
    public function __construct(
        private string $projectDir,
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
                ['label' => 'Default enabled bundles', 'value' => (string) count($defaultLock['enabledBundles'])],
                ['label' => 'Production enabled bundles', 'value' => (string) count($prodLock['enabledBundles'])],
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

    /** @return array{scopeType: string, file: string, path: string, status: string, sha256: ?string, scope: ?string, strict: ?bool, sourceComposerFile: ?string, sourceComposerSha256: ?string, generatedAt: ?string, generatedBy: ?string, enabledBundles: list<string>, disabledComponents: list<string>, errors: list<string>} */
    private function lockReport(string $hostDir, string $relativePath, string $scopeType): array
    {
        $path = rtrim($hostDir, '/\\').'/'.$relativePath;
        if (!is_file($path)) {
            return [
                'scopeType' => $scopeType,
                'file' => basename($relativePath),
                'path' => $path,
                'status' => 'missing',
                'sha256' => null,
                'scope' => null,
                'strict' => null,
                'sourceComposerFile' => null,
                'sourceComposerSha256' => null,
                'generatedAt' => null,
                'generatedBy' => null,
                'enabledBundles' => [],
                'disabledComponents' => [],
                'errors' => [sprintf('Runtime scope lock is missing: %s', $path)],
            ];
        }

        try {
            $payload = require $path;
            if (!is_array($payload)) {
                throw new \RuntimeException('Runtime scope lock must return an array.');
            }
        } catch (\Throwable $exception) {
            return [
                'scopeType' => $scopeType,
                'file' => basename($relativePath),
                'path' => $path,
                'status' => 'unreadable',
                'sha256' => hash_file('sha256', $path) ?: null,
                'scope' => null,
                'strict' => null,
                'sourceComposerFile' => null,
                'sourceComposerSha256' => null,
                'generatedAt' => null,
                'generatedBy' => null,
                'enabledBundles' => [],
                'disabledComponents' => [],
                'errors' => [sprintf('Unable to read %s: %s', $path, $exception->getMessage())],
            ];
        }

        return [
            'scopeType' => $scopeType,
            'file' => basename($relativePath),
            'path' => $path,
            'status' => 'present',
            'sha256' => hash_file('sha256', $path) ?: null,
            'scope' => is_string($payload['scope'] ?? null) ? $payload['scope'] : null,
            'strict' => is_bool($payload['strict'] ?? null) ? $payload['strict'] : null,
            'sourceComposerFile' => is_string($payload['sourceComposerFile'] ?? null) ? $payload['sourceComposerFile'] : null,
            'sourceComposerSha256' => is_string($payload['sourceComposerSha256'] ?? null) ? $payload['sourceComposerSha256'] : null,
            'generatedAt' => is_string($payload['generatedAt'] ?? null) ? $payload['generatedAt'] : null,
            'generatedBy' => is_string($payload['generatedBy'] ?? null) ? $payload['generatedBy'] : null,
            'enabledBundles' => $this->stringList($payload['enabledBundles'] ?? []),
            'disabledComponents' => $this->stringList($payload['disabledComponents'] ?? []),
            'errors' => [],
        ];
    }

    /** @return list<string> */
    private function stringList(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        $result = [];
        foreach ($payload as $item) {
            if (is_string($item) && '' !== trim($item)) {
                $result[] = trim($item);
            }
        }

        return array_values(array_unique($result));
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
