<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Scanner\RuntimeScope\AdministrationRuntimeScopeConfigLeakScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:report',
    description: 'Reports host runtime-scope lock, composer inventory, bundle autoloadability, and disabled-component config leaks.',
)]
final class AdministrationRuntimeScopeReportCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopeConfigLeakScanner $configLeakScanner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', $this->projectDir)
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Runtime environment to inspect. Uses prod lock only for prod.', 'prod')
            ->addOption('max-age-seconds', null, InputOption::VALUE_REQUIRED, 'Fail when generatedAt is older than this many seconds. Use 0 to disable.', '0')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostDir = $this->absolutePath((string) $input->getOption('host-dir'));
        $environment = (string) $input->getOption('env');
        $maxAgeSeconds = max(0, (int) $input->getOption('max-age-seconds'));

        $report = $this->buildReport($hostDir, $environment, $maxAgeSeconds);
        $status = [] === $report['errors'] ? Command::SUCCESS : Command::FAILURE;

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $status;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Administration runtime-scope report');
        $io->writeln(sprintf('Host: <info>%s</info>', $hostDir));
        $io->writeln(sprintf('Environment: <info>%s</info>', $environment));
        $io->writeln(sprintf('Lock: <info>%s</info>', $report['lock']['path']));
        $io->writeln(sprintf('Lock status: <info>%s</info>', $report['lock']['status']));
        $io->writeln(sprintf('Composer: <info>%s</info>', $report['composer']['file']));
        $io->writeln(sprintf('Composer status: <info>%s</info>', $report['composer']['status']));

        $io->table(
            ['Bundle class', 'Status'],
            array_map(static fn (array $bundle): array => [$bundle['class'], $bundle['status']], $report['enabledBundles']),
        );

        if ([] !== $report['configLeaks']) {
            $io->table(
                ['File', 'Line', 'Component', 'Pattern', 'Excerpt'],
                array_map(static fn (array $finding): array => [
                    $finding['file'],
                    (string) $finding['line'],
                    $finding['component'],
                    $finding['pattern'],
                    $finding['excerpt'],
                ], $report['configLeaks']),
            );
        }

        if ([] !== $report['errors']) {
            $io->error($report['errors']);

            return Command::FAILURE;
        }

        $io->success('Runtime-scope report is clean.');

        return Command::SUCCESS;
    }

    /**
     * @return array{
     *   report: string,
     *   hostDir: string,
     *   environment: string,
     *   lock: array<string, mixed>,
     *   composer: array<string, mixed>,
     *   enabledBundles: list<array{class: string, status: string}>,
     *   disabledComponents: list<string>,
     *   configLeaks: list<array{file: string, line: int, component: string, pattern: string, excerpt: string}>,
     *   errors: list<string>
     * }
     */
    private function buildReport(string $hostDir, string $environment, int $maxAgeSeconds): array
    {
        $errors = [];
        $lockPath = $this->lockPath($hostDir, $environment);
        $expectedComposerFile = 'prod' === $environment ? 'composer.prod.json' : 'composer.json';
        $composerPath = rtrim($hostDir, '/\\').'/'.$expectedComposerFile;
        $composer = $this->composerReport($composerPath, $expectedComposerFile);

        $lock = [
            'path' => $lockPath,
            'status' => 'missing',
            'scope' => null,
            'strict' => 'prod' === $environment,
            'sourceComposerFile' => null,
            'sourceComposerSha256' => null,
            'generatedAt' => null,
        ];
        $enabledBundleReports = [];
        $disabledComponents = [];
        $configLeaks = [];

        if (!is_file($lockPath)) {
            $errors[] = sprintf('Runtime scope lock is missing: %s', $lockPath);
        } else {
            try {
                $lockPayload = require $lockPath;
                if (!is_array($lockPayload)) {
                    throw new \RuntimeException('Lock file must return an array.');
                }

                $lock = array_merge($lock, [
                    'status' => 'present',
                    'scope' => is_string($lockPayload['scope'] ?? null) ? $lockPayload['scope'] : null,
                    'strict' => is_bool($lockPayload['strict'] ?? null) ? $lockPayload['strict'] : ('prod' === $environment),
                    'sourceComposerFile' => is_string($lockPayload['sourceComposerFile'] ?? null) ? $lockPayload['sourceComposerFile'] : null,
                    'sourceComposerSha256' => is_string($lockPayload['sourceComposerSha256'] ?? null) ? $lockPayload['sourceComposerSha256'] : null,
                    'sourceComposerPackageCount' => is_int($lockPayload['sourceComposerPackageCount'] ?? null) ? $lockPayload['sourceComposerPackageCount'] : null,
                    'generatedAt' => is_string($lockPayload['generatedAt'] ?? null) ? $lockPayload['generatedAt'] : null,
                    'generatedBy' => is_string($lockPayload['generatedBy'] ?? null) ? $lockPayload['generatedBy'] : null,
                ]);

                $enabledBundles = $lockPayload['enabledBundles'] ?? [];
                if (!is_array($enabledBundles)) {
                    $errors[] = sprintf('enabledBundles must be an array in %s.', $lockPath);
                    $enabledBundles = [];
                }

                foreach ($enabledBundles as $bundleClass) {
                    if (!is_string($bundleClass) || '' === $bundleClass) {
                        $errors[] = sprintf('Invalid enabled bundle class in %s.', $lockPath);
                        continue;
                    }

                    $classStatus = class_exists($bundleClass) ? 'autoloadable' : 'missing';
                    if ('missing' === $classStatus && true === $lock['strict']) {
                        $errors[] = sprintf('Enabled bundle class is missing in strict runtime scope: %s', $bundleClass);
                    }
                    $enabledBundleReports[] = ['class' => $bundleClass, 'status' => $classStatus];
                }

                $disabledComponentsPayload = $lockPayload['disabledComponents'] ?? [];
                if (is_array($disabledComponentsPayload)) {
                    foreach ($disabledComponentsPayload as $disabledComponent) {
                        if (is_string($disabledComponent) && '' !== trim($disabledComponent)) {
                            $disabledComponents[] = strtolower(trim($disabledComponent));
                        }
                    }
                    $disabledComponents = array_values(array_unique($disabledComponents));
                }

                $this->validateComposerFingerprint($lock, $composer, $expectedComposerFile, $errors);
                $this->validateGeneratedAt($lock, $maxAgeSeconds, $errors);
                $configLeaks = $this->configLeakScanner->scan($hostDir, $disabledComponents);
                foreach ($configLeaks as $finding) {
                    $errors[] = sprintf(
                        'Disabled component "%s" leaks into %s:%d through pattern "%s".',
                        $finding['component'],
                        $finding['file'],
                        $finding['line'],
                        $finding['pattern'],
                    );
                }
            } catch (\Throwable $exception) {
                $errors[] = sprintf('Unable to read runtime scope lock %s: %s', $lockPath, $exception->getMessage());
            }
        }

        if ('missing' === $composer['status'] && 'prod' === $environment) {
            $errors[] = sprintf('Production composer inventory is missing: %s', $composerPath);
        }

        return [
            'report' => 'administration_runtime_scope_report',
            'hostDir' => $hostDir,
            'environment' => $environment,
            'lock' => $lock,
            'composer' => $composer,
            'enabledBundles' => $enabledBundleReports,
            'disabledComponents' => $disabledComponents,
            'configLeaks' => $configLeaks,
            'errors' => array_values(array_unique($errors)),
        ];
    }

    /** @param array<string, mixed> $lock */
    private function validateComposerFingerprint(array $lock, array $composer, string $expectedComposerFile, array &$errors): void
    {
        if (($lock['sourceComposerFile'] ?? null) !== null && $lock['sourceComposerFile'] !== $expectedComposerFile) {
            $errors[] = sprintf(
                'Lock sourceComposerFile mismatch: expected %s, got %s.',
                $expectedComposerFile,
                (string) $lock['sourceComposerFile'],
            );
        }

        if (($lock['sourceComposerSha256'] ?? null) !== null && null !== $composer['sha256'] && $lock['sourceComposerSha256'] !== $composer['sha256']) {
            $errors[] = sprintf('Lock composer fingerprint mismatch for %s. Regenerate runtime scope lock.', $expectedComposerFile);
        }
    }

    /** @param array<string, mixed> $lock */
    private function validateGeneratedAt(array $lock, int $maxAgeSeconds, array &$errors): void
    {
        if ($maxAgeSeconds <= 0) {
            return;
        }

        $generatedAt = $lock['generatedAt'] ?? null;
        if (!is_string($generatedAt) || '' === $generatedAt) {
            $errors[] = 'Lock generatedAt is missing while max-age check is enabled.';

            return;
        }

        try {
            $generatedAtDate = new \DateTimeImmutable($generatedAt);
        } catch (\Throwable) {
            $errors[] = sprintf('Lock generatedAt is invalid: %s', $generatedAt);

            return;
        }

        if ((time() - $generatedAtDate->getTimestamp()) > $maxAgeSeconds) {
            $errors[] = sprintf('Runtime scope lock is older than %d seconds.', $maxAgeSeconds);
        }
    }

    /** @return array{file: string, path: string, status: string, sha256: string|null, packageCount: int} */
    private function composerReport(string $composerPath, string $composerFile): array
    {
        if (!is_file($composerPath)) {
            return [
                'file' => $composerFile,
                'path' => $composerPath,
                'status' => 'missing',
                'sha256' => null,
                'packageCount' => 0,
            ];
        }

        return [
            'file' => $composerFile,
            'path' => $composerPath,
            'status' => 'present',
            'sha256' => hash_file('sha256', $composerPath) ?: null,
            'packageCount' => $this->composerPackageCount($composerPath),
        ];
    }

    private function composerPackageCount(string $composerPath): int
    {
        try {
            $json = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return 0;
        }

        if (!is_array($json) || !is_array($json['require'] ?? null)) {
            return 0;
        }

        return count(array_filter(array_keys($json['require']), static fn (string $package): bool => 'php' !== $package));
    }

    private function lockPath(string $hostDir, string $environment): string
    {
        $fileName = 'prod' === $environment ? 'runtime_scope.prod.lock.php' : 'runtime_scope.lock.php';

        return rtrim($hostDir, '/\\').'/config/kernel/'.$fileName;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\/]/', $path)) {
            return rtrim($path, '/\\');
        }

        return rtrim($this->projectDir, '/\\').'/'.trim($path, '/\\');
    }
}
