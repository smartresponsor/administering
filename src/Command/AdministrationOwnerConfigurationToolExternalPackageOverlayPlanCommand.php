<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageOverlayPlanReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:external-package-overlay-plan',
    description: 'Builds a reviewed non-destructive overlay plan from an owner-side external package manifest.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageOverlayPlanCommand extends Command
{
    private const EXPECTED_SCHEMA = 'smart-responsor.administering.owner_configuration_external_package_manifest.v1';

    protected function configure(): void
    {
        $this
            ->addArgument('manifest', InputArgument::REQUIRED, 'Path to owner external package manifest JSON.')
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional component key/token filter, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print overlay plan as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write overlay plan to a JSON file path.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when the manifest contains no component overlay plans.')
            ->addOption('allow-issues', null, InputOption::VALUE_NONE, 'Do not fail when non-fatal manifest/plan issues are found. Transitional/manual review only.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestPath = (string) $input->getArgument('manifest');
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));

        if (!is_file($manifestPath)) {
            $io->error(sprintf('External package manifest not found: %s', $manifestPath));

            return Command::FAILURE;
        }

        try {
            $payload = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $io->error(sprintf('External package manifest is not valid JSON: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        if (!is_array($payload)) {
            $io->error('External package manifest root must be a JSON object.');

            return Command::FAILURE;
        }

        $report = $this->buildOverlayPlan($manifestPath, $payload, $componentFilter);
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));

        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create overlay plan directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writeJson, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side external package overlay plan written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-issues'));
        }

        $io->section('Owner-side external package overlay plan');
        $io->writeln(sprintf('Manifest: <info>%s</info>', $manifestPath));
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Manifest accepted: <info>%s</info>', $report->manifestAccepted ? 'yes' : 'no'));
        $io->writeln(sprintf('Components: <info>%d</info>', $report->componentCount()));
        $io->writeln(sprintf('Overlay files: <info>%d</info>', $report->fileCount()));
        $io->writeln(sprintf('Errors: <comment>%d</comment>', $report->errorCount()));
        $io->writeln(sprintf('Warnings: <comment>%d</comment>', $report->warningCount()));

        foreach ($report->componentPlans as $plan) {
            $io->section(sprintf('%s overlay plan', $plan['componentKey']));
            $io->writeln(sprintf('Repository root token: <info>%s</info>', $plan['componentToken']));
            $io->writeln(sprintf('Package root: <info>%s</info>', $plan['packageRoot']));
            $io->writeln(sprintf('Provider: <info>%s</info>', $plan['providerClass']));
            $io->writeln(sprintf('Delivery: <info>%s</info>', $plan['deliveryMode']));
            $io->table(
                ['Overlay file', 'Kind'],
                array_map(static fn (array $file): array => [
                    $file['path'],
                    $file['kind'],
                ], $plan['overlayFiles']),
            );
        }

        if ([] !== $report->issues) {
            $io->section('Overlay plan issues');
            $io->table(
                ['Severity', 'Path', 'Message'],
                array_map(static fn (array $issue): array => [
                    $issue['severity'],
                    $issue['path'],
                    $issue['message'],
                ], $report->issues),
            );
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-issues'));
    }

    /** @param array<string, mixed> $payload */
    private function buildOverlayPlan(string $manifestPath, array $payload, ?string $componentFilter): AdministrationOwnerConfigurationToolExternalPackageOverlayPlanReport
    {
        $issues = [];
        $componentPlans = [];
        $manifestAccepted = self::EXPECTED_SCHEMA === ($payload['schema'] ?? null);

        if (!$manifestAccepted) {
            $issues[] = $this->issue('error', 'schema', sprintf('Expected schema %s.', self::EXPECTED_SCHEMA));
        }

        $this->requireLiteral($payload, 'deliveryMode', 'overlay_only', $issues);
        $this->requireLiteral($payload, 'deleteMode', 'none', $issues);
        $this->requireLiteral($payload, 'automaticMoveAllowed', false, $issues);

        $componentManifests = $payload['componentManifests'] ?? null;
        if (!is_array($componentManifests)) {
            $issues[] = $this->issue('error', 'componentManifests', 'componentManifests must be an array.');

            return new AdministrationOwnerConfigurationToolExternalPackageOverlayPlanReport($manifestPath, $manifestAccepted, $componentPlans, $issues);
        }

        foreach ($componentManifests as $index => $componentManifest) {
            $componentPath = sprintf('componentManifests[%d]', $index);
            if (!is_array($componentManifest)) {
                $issues[] = $this->issue('error', $componentPath, 'Component manifest must be an object.');
                continue;
            }

            $componentKey = $this->stringValue($componentManifest['componentKey'] ?? null) ?? 'Unknown';
            $componentToken = $this->stringValue($componentManifest['componentToken'] ?? null) ?? strtolower($componentKey);
            if (!$this->matchesComponentFilter($componentKey, $componentToken, $componentFilter)) {
                continue;
            }

            $this->requireLiteral($componentManifest, 'deliveryMode', 'overlay_only', $issues, $componentPath.'.deliveryMode');
            $this->requireLiteral($componentManifest, 'deleteMode', 'none', $issues, $componentPath.'.deleteMode');
            $this->requireLiteral($componentManifest, 'automaticMoveAllowed', false, $issues, $componentPath.'.automaticMoveAllowed');

            $files = is_array($componentManifest['files'] ?? null) ? $componentManifest['files'] : [];
            $overlayFiles = [];
            foreach ($files as $fileIndex => $file) {
                if (!is_string($file) || '' === trim($file)) {
                    $issues[] = $this->issue('error', sprintf('%s.files[%d]', $componentPath, $fileIndex), 'File path must be a non-empty string.');
                    continue;
                }

                $path = trim($file);
                if (!$this->isSafeRelativePath($path)) {
                    $issues[] = $this->issue('error', sprintf('%s.files[%d]', $componentPath, $fileIndex), 'File path must be repository-relative and must not contain traversal.');
                    continue;
                }

                $overlayFiles[] = [
                    'path' => $path,
                    'kind' => $this->fileKind($path),
                    'copyMode' => 'overlay_only',
                    'deleteMode' => 'none',
                    'automaticMoveAllowed' => false,
                ];
            }

            $componentPlans[] = [
                'componentKey' => $componentKey,
                'componentToken' => $componentToken,
                'packageRoot' => $componentManifest['packageRoot'] ?? $componentKey,
                'providerClass' => $componentManifest['providerClass'] ?? '',
                'deliveryMode' => 'overlay_only',
                'deleteMode' => 'none',
                'automaticMoveAllowed' => false,
                'overlayFileCount' => count($overlayFiles),
                'overlayFiles' => $overlayFiles,
                'manualReviewRequired' => true,
                'applyCommandPolicy' => 'overlay_existing_repository_only',
            ];
        }

        if ([] !== ($payload['rejectedEntries'] ?? [])) {
            $issues[] = $this->issue('warning', 'rejectedEntries', 'Manifest contains rejected entries. Overlay plan excludes rejected entries and requires owner-side review.');
        }

        return new AdministrationOwnerConfigurationToolExternalPackageOverlayPlanReport($manifestPath, $manifestAccepted, $componentPlans, $issues);
    }

    /** @param array<string, mixed> $payload @param list<array<string, string>> $issues */
    private function requireLiteral(array $payload, string $key, mixed $expected, array &$issues, ?string $path = null): void
    {
        if (($payload[$key] ?? null) !== $expected) {
            $issues[] = $this->issue('error', $path ?? $key, sprintf('Expected literal %s.', json_encode($expected, JSON_THROW_ON_ERROR)));
        }
    }

    private function matchesComponentFilter(string $componentKey, string $componentToken, ?string $componentFilter): bool
    {
        if (null === $componentFilter) {
            return true;
        }

        return strtolower($componentFilter) === strtolower($componentKey) || strtolower($componentFilter) === strtolower($componentToken);
    }

    private function isSafeRelativePath(string $path): bool
    {
        return '' !== $path
            && !str_contains($path, '..')
            && !str_starts_with($path, '/')
            && !preg_match('/^[A-Za-z]:[\\\/]/', $path);
    }

    private function fileKind(string $path): string
    {
        return match (true) {
            str_contains($path, '/src/Provider/Configuration/') => 'provider',
            str_contains($path, '/src/Service/Configuration/') => 'service',
            str_contains($path, '/src/Form/Configuration/') => 'form_type',
            str_contains($path, '/src/Value/Form/Configuration/') => 'form_data',
            str_contains($path, '/config/services/') => 'service_config',
            default => 'support',
        };
    }

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageOverlayPlanReport $report, bool $allowEmpty, bool $allowIssues): int
    {
        if (0 === $report->componentCount() && !$allowEmpty) {
            return Command::FAILURE;
        }

        if ($report->hasErrors()) {
            return Command::FAILURE;
        }

        if (0 < $report->warningCount() && !$allowIssues) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /** @return array<string, string> */
    private function issue(string $severity, string $path, string $message): array
    {
        return [
            'severity' => $severity,
            'path' => $path,
            'message' => $message,
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
