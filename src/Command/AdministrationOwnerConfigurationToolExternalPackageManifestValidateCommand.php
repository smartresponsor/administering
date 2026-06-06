<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageManifestValidationReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:external-package-manifest:validate',
    description: 'Validates a non-destructive owner-side external package manifest before neighbor overlays.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageManifestValidateCommand extends Command
{
    private const EXPECTED_SCHEMA = 'smart-responsor.administering.owner_configuration_external_package_manifest.v1';

    protected function configure(): void
    {
        $this
            ->addArgument('manifest', InputArgument::REQUIRED, 'Path to owner external package manifest JSON.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print validation report as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write validation report to a JSON file path.')
            ->addOption('allow-warnings', null, InputOption::VALUE_NONE, 'Do not fail when warnings are found.')
            ->addOption('allow-errors', null, InputOption::VALUE_NONE, 'Do not fail when errors are found. Transitional/manual review only.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $manifestPath = (string) $input->getArgument('manifest');

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

        $report = $this->validateManifest($manifestPath, $payload);
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));

        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create validation report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writeJson, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('External package manifest validation report written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-warnings'), (bool) $input->getOption('allow-errors'));
        }

        $io->section('Owner-side external package manifest validation');
        $io->writeln(sprintf('Manifest: <info>%s</info>', $manifestPath));
        $io->writeln(sprintf('Schema accepted: <info>%s</info>', $report->schemaAccepted ? 'yes' : 'no'));
        $io->writeln(sprintf('Components: <info>%d</info>', $report->componentCount()));
        $io->writeln(sprintf('Errors: <comment>%d</comment>', $report->errorCount()));
        $io->writeln(sprintf('Warnings: <comment>%d</comment>', $report->warningCount()));

        if ([] !== $report->componentSummaries) {
            $io->table(
                ['Component', 'Token', 'Tools', 'Files', 'Provider'],
                array_map(static fn (array $summary): array => [
                    $summary['componentKey'],
                    $summary['componentToken'],
                    $summary['toolCount'],
                    $summary['fileCount'],
                    $summary['providerClass'],
                ], $report->componentSummaries),
            );
        }

        if ([] !== $report->issues) {
            $io->section('Manifest issues');
            $io->table(
                ['Severity', 'Path', 'Message'],
                array_map(static fn (array $issue): array => [
                    $issue['severity'],
                    $issue['path'],
                    $issue['message'],
                ], $report->issues),
            );
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-warnings'), (bool) $input->getOption('allow-errors'));
    }

    /** @param array<string, mixed> $payload */
    private function validateManifest(string $manifestPath, array $payload): AdministrationOwnerConfigurationToolExternalPackageManifestValidationReport
    {
        $issues = [];
        $componentSummaries = [];
        $schemaAccepted = self::EXPECTED_SCHEMA === ($payload['schema'] ?? null);

        if (!$schemaAccepted) {
            $issues[] = $this->issue('error', 'schema', sprintf('Expected schema %s.', self::EXPECTED_SCHEMA));
        }

        $this->requireLiteral($payload, 'deliveryMode', 'overlay_only', $issues);
        $this->requireLiteral($payload, 'deleteMode', 'none', $issues);
        $this->requireLiteral($payload, 'automaticMoveAllowed', false, $issues);

        $components = $payload['componentManifests'] ?? null;
        if (!is_array($components)) {
            $issues[] = $this->issue('error', 'componentManifests', 'componentManifests must be an array.');

            return new AdministrationOwnerConfigurationToolExternalPackageManifestValidationReport($manifestPath, $schemaAccepted, $issues, $componentSummaries);
        }

        $allFiles = [];
        foreach ($components as $index => $componentManifest) {
            $componentPath = sprintf('componentManifests[%d]', $index);
            if (!is_array($componentManifest)) {
                $issues[] = $this->issue('error', $componentPath, 'Component manifest must be an object.');
                continue;
            }

            $componentKey = $this->stringValue($componentManifest['componentKey'] ?? null);
            $componentToken = $this->stringValue($componentManifest['componentToken'] ?? null);
            $providerClass = $this->stringValue($componentManifest['providerClass'] ?? null);
            $tools = is_array($componentManifest['tools'] ?? null) ? $componentManifest['tools'] : [];
            $files = is_array($componentManifest['files'] ?? null) ? $componentManifest['files'] : [];

            if (null === $componentKey || '' === $componentKey) {
                $issues[] = $this->issue('error', $componentPath.'.componentKey', 'componentKey is required.');
            }
            if (null === $componentToken || '' === $componentToken || strtolower($componentToken) !== $componentToken) {
                $issues[] = $this->issue('error', $componentPath.'.componentToken', 'componentToken is required and must be lowercase.');
            }
            if (null === $providerClass || '' === $providerClass) {
                $issues[] = $this->issue('error', $componentPath.'.providerClass', 'providerClass is required.');
            }

            $this->requireLiteral($componentManifest, 'deliveryMode', 'overlay_only', $issues, $componentPath.'.deliveryMode');
            $this->requireLiteral($componentManifest, 'deleteMode', 'none', $issues, $componentPath.'.deleteMode');
            $this->requireLiteral($componentManifest, 'automaticMoveAllowed', false, $issues, $componentPath.'.automaticMoveAllowed');

            foreach ($files as $fileIndex => $file) {
                if (!is_string($file) || '' === trim($file)) {
                    $issues[] = $this->issue('error', sprintf('%s.files[%d]', $componentPath, $fileIndex), 'File path must be a non-empty string.');
                    continue;
                }
                if (str_contains($file, '..') || str_starts_with($file, '/') || preg_match('~^[A-Za-z]:[\\\\/]~', $file)) {
                    $issues[] = $this->issue('error', sprintf('%s.files[%d]', $componentPath, $fileIndex), 'File path must be repository-relative and must not contain traversal.');
                }
                if (isset($allFiles[$file])) {
                    $issues[] = $this->issue('error', sprintf('%s.files[%d]', $componentPath, $fileIndex), sprintf('Duplicate overlay target also listed by %s.', $allFiles[$file]));
                }
                $allFiles[$file] = $componentPath;
            }

            foreach ($tools as $toolIndex => $tool) {
                $toolPath = sprintf('%s.tools[%d]', $componentPath, $toolIndex);
                if (!is_array($tool)) {
                    $issues[] = $this->issue('error', $toolPath, 'Tool entry must be an object.');
                    continue;
                }

                $this->validateToolEntry($tool, $toolPath, $componentKey, $componentToken, $issues);
            }

            $componentSummaries[] = [
                'componentKey' => $componentKey ?? 'Unknown',
                'componentToken' => $componentToken ?? 'unknown',
                'providerClass' => $providerClass ?? '-',
                'toolCount' => count($tools),
                'fileCount' => count($files),
            ];
        }

        $rejected = $payload['rejectedEntries'] ?? [];
        if (is_array($rejected) && [] !== $rejected) {
            $issues[] = $this->issue('warning', 'rejectedEntries', sprintf('Manifest contains %d rejected entries; review before neighbor handoff.', count($rejected)));
        }

        return new AdministrationOwnerConfigurationToolExternalPackageManifestValidationReport($manifestPath, $schemaAccepted, $issues, $componentSummaries);
    }

    /** @param array<string, mixed> $tool @param list<array<string, string>> $issues */
    /**
     * @param array<string, mixed>        $tool
     * @param list<array<string, string>> $issues
     */
    private function validateToolEntry(array $tool, string $toolPath, ?string $componentKey, ?string $componentToken, array &$issues): void
    {
        $toolKey = $this->stringValue($tool['toolKey'] ?? null);
        $toolSlug = $this->stringValue($tool['toolSlug'] ?? null);
        $serviceShortName = $this->stringValue($tool['serviceShortName'] ?? null);
        $servicePath = $this->stringValue($tool['servicePath'] ?? null);

        if (null === $toolKey || null === $componentToken || !str_starts_with($toolKey, $componentToken.'.')) {
            $issues[] = $this->issue('error', $toolPath.'.toolKey', 'toolKey must start with the component token followed by a dot.');
        }
        if (null === $toolSlug || !preg_match('/^[A-Z][A-Za-z0-9]*$/', $toolSlug)) {
            $issues[] = $this->issue('error', $toolPath.'.toolSlug', 'toolSlug must be PascalCase.');
        }
        if (null !== $componentKey && null !== $toolSlug && null !== $serviceShortName) {
            $expected = sprintf('%sConfiguration%sService', $componentKey, $toolSlug);
            if ($expected !== $serviceShortName) {
                $issues[] = $this->issue('error', $toolPath.'.serviceShortName', sprintf('Expected owner-side service short name %s.', $expected));
            }
        }
        if (null === $servicePath || null === $componentKey || !str_starts_with($servicePath, $componentKey.'/src/Service/Configuration/')) {
            $issues[] = $this->issue('error', $toolPath.'.servicePath', 'servicePath must target the owner component Service/Configuration layer.');
        }

        $this->requireLiteral($tool, 'copyMode', 'overlay_only', $issues, $toolPath.'.copyMode');
        $this->requireLiteral($tool, 'deleteMode', 'none', $issues, $toolPath.'.deleteMode');
        $this->requireLiteral($tool, 'automaticMoveAllowed', false, $issues, $toolPath.'.automaticMoveAllowed');
    }

    /** @param array<string, mixed> $payload @param list<array<string, string>> $issues */
    /**
     * @param array<string, mixed>        $payload
     * @param list<array<string, string>> $issues
     */
    private function requireLiteral(array $payload, string $key, mixed $expected, array &$issues, ?string $path = null): void
    {
        if (($payload[$key] ?? null) !== $expected) {
            $issues[] = $this->issue('error', $path ?? $key, sprintf('Expected literal %s.', json_encode($expected, JSON_THROW_ON_ERROR)));
        }
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
        return is_string($value) ? trim($value) : null;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageManifestValidationReport $report, bool $allowWarnings, bool $allowErrors): int
    {
        if ($report->hasErrors() && !$allowErrors) {
            return Command::FAILURE;
        }

        if (0 < $report->warningCount() && !$allowWarnings) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
