<?php

declare(strict_types=1);

namespace App\Administering\Service\RuntimeScope;

use App\Administering\Entity\AdministrationEnvironmentRuntimeRecord;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeSourceIndex;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AdministrationComposerIndexService
{
    public function __construct(
        private string $projectDir,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function index(string $hostDir = ''): AdministrationRuntimeSourceIndex
    {
        $hostDir = $this->absoluteHostDir($hostDir);
        $composerFiles = [
            'composer.json' => $this->composerFileReport($hostDir, 'composer.json'),
            'composer.prod.json' => $this->composerFileReport($hostDir, 'composer.prod.json'),
        ];
        $registeredRecords = $this->registeredComposerRecords();

        return new AdministrationRuntimeSourceIndex(
            title: 'Composer inventory',
            description: 'Physical inventory read from composer.json and composer.prod.json. SQLite records are displayed as registered observations, not as runtime truth.',
            summaryItems: [
                ['label' => 'Host', 'value' => $hostDir],
                ['label' => 'composer.json packages', 'value' => (string) count($composerFiles['composer.json']['packages'])],
                ['label' => 'composer.prod.json packages', 'value' => (string) count($composerFiles['composer.prod.json']['packages'])],
                ['label' => 'SQLite registered records', 'value' => (string) count($registeredRecords)],
            ],
            sections: [
                [
                    'title' => 'composer.json',
                    'kind' => 'composer_file',
                    'rows' => [$composerFiles['composer.json']],
                ],
                [
                    'title' => 'composer.prod.json',
                    'kind' => 'composer_file',
                    'rows' => [$composerFiles['composer.prod.json']],
                ],
                [
                    'title' => 'Registered Composer observations from SQLite',
                    'kind' => 'registered_records',
                    'rows' => $registeredRecords,
                ],
            ],
            errors: array_values(array_filter([
                ...$composerFiles['composer.json']['errors'],
                ...$composerFiles['composer.prod.json']['errors'],
            ])),
        );
    }

    /** @return array{file: string, path: string, status: string, sha256: ?string, packageCount: int, packages: list<string>, repositoryCount: int, errors: list<string>} */
    private function composerFileReport(string $hostDir, string $file): array
    {
        $path = rtrim($hostDir, '/\\').'/'.$file;
        if (!is_file($path)) {
            return [
                'file' => $file,
                'path' => $path,
                'status' => 'missing',
                'sha256' => null,
                'packageCount' => 0,
                'packages' => [],
                'repositoryCount' => 0,
                'errors' => [sprintf('Composer file is missing: %s', $path)],
            ];
        }

        try {
            $payload = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            return [
                'file' => $file,
                'path' => $path,
                'status' => 'unreadable',
                'sha256' => hash_file('sha256', $path) ?: null,
                'packageCount' => 0,
                'packages' => [],
                'repositoryCount' => 0,
                'errors' => [sprintf('Unable to parse %s: %s', $path, $exception->getMessage())],
            ];
        }

        $packages = [];
        foreach (['require', 'require-dev'] as $section) {
            if (!is_array($payload[$section] ?? null)) {
                continue;
            }

            foreach (array_keys($payload[$section]) as $package) {
                if (is_string($package) && '' !== trim($package)) {
                    $packages[] = $section.': '.$package;
                }
            }
        }

        sort($packages);

        return [
            'file' => $file,
            'path' => $path,
            'status' => 'present',
            'sha256' => hash_file('sha256', $path) ?: null,
            'packageCount' => count($packages),
            'packages' => $packages,
            'repositoryCount' => is_array($payload['repositories'] ?? null) ? count($payload['repositories']) : 0,
            'errors' => [],
        ];
    }

    /** @return list<array{environmentKey: string, category: string, status: string, sourceType: string, checkedAt: string, context: string}> */
    private function registeredComposerRecords(): array
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationEnvironmentRuntimeRecord::class);
        if (null === $manager) {
            return [];
        }

        $records = $manager->getRepository(AdministrationEnvironmentRuntimeRecord::class)->findBy([], ['id' => 'DESC'], 200);
        $result = [];
        foreach ($records as $record) {
            if (!$record instanceof AdministrationEnvironmentRuntimeRecord) {
                continue;
            }

            $key = strtolower($record->getEnvironmentKey().' '.$record->getCategory().' '.$record->getSourceType());
            if (!str_contains($key, 'composer')) {
                continue;
            }

            $result[] = [
                'environmentKey' => $record->getEnvironmentKey(),
                'category' => $record->getCategory(),
                'status' => $record->getStatus(),
                'sourceType' => $record->getSourceType(),
                'checkedAt' => $record->getCheckedAt()->format(\DateTimeInterface::ATOM),
                'context' => json_encode($record->getSafeContext(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            ];
        }

        return $result;
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
