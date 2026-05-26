<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:external-package-apply-script',
    description: 'Generates a non-destructive PowerShell overlay script from an owner-side external package overlay plan.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageApplyScriptCommand extends Command
{
    private const EXPECTED_SCHEMA = 'smart-responsor.administering.owner_configuration_external_package_overlay_plan.v1';

    protected function configure(): void
    {
        $this
            ->addArgument('overlay-plan', InputArgument::REQUIRED, 'Path to owner external package overlay plan JSON.')
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional component key/token filter, for example Managing or managing.')
            ->addOption('write-ps1', null, InputOption::VALUE_REQUIRED, 'Write generated non-destructive PowerShell overlay script to this path.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print generation report as JSON.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when the overlay plan contains no component plans.')
            ->addOption('allow-issues', null, InputOption::VALUE_NONE, 'Do not fail when script generation report contains non-fatal issues.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $overlayPlanPath = (string) $input->getArgument('overlay-plan');
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $writePs1 = $this->normalizeOptionalString($input->getOption('write-ps1'));

        if (!is_file($overlayPlanPath)) {
            $io->error(sprintf('External package overlay plan not found: %s', $overlayPlanPath));

            return Command::FAILURE;
        }

        try {
            $payload = json_decode((string) file_get_contents($overlayPlanPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $io->error(sprintf('External package overlay plan is not valid JSON: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        if (!is_array($payload)) {
            $io->error('External package overlay plan root must be a JSON object.');

            return Command::FAILURE;
        }

        $report = $this->buildReport($overlayPlanPath, $payload, $componentFilter);
        $script = $this->buildPowerShellScript($report);

        if (null !== $writePs1) {
            $targetDirectory = dirname($writePs1);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create PowerShell script directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writePs1, $script);
            $io->success(sprintf('Owner-side external package PowerShell overlay script written to %s.', $writePs1));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-issues'));
        }

        $io->section('Owner-side external package apply script report');
        $io->writeln(sprintf('Overlay plan: <info>%s</info>', $overlayPlanPath));
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Plan accepted: <info>%s</info>', $report->planAccepted ? 'yes' : 'no'));
        $io->writeln(sprintf('Components: <info>%d</info>', $report->componentCount()));
        $io->writeln(sprintf('Overlay files: <info>%d</info>', $report->fileCount()));
        $io->writeln(sprintf('Errors: <comment>%d</comment>', $report->errorCount()));
        $io->writeln(sprintf('Warnings: <comment>%d</comment>', $report->warningCount()));

        foreach ($report->componentPlans as $plan) {
            $io->section(sprintf('%s apply script inputs', $plan['componentKey']));
            $io->writeln(sprintf('Component token: <info>%s</info>', $plan['componentToken']));
            $io->writeln(sprintf('Default repository root: <info>$WorkspaceRoot\%s</info>', $plan['componentKey']));
            $io->table(
                ['Source in package', 'Target in repository', 'Kind'],
                array_map(static fn (array $file): array => [
                    $file['packagePath'],
                    $file['targetRelativePath'],
                    $file['kind'],
                ], $plan['overlayFiles']),
            );
        }

        if ([] !== $report->issues) {
            $io->section('Apply script generation issues');
            $io->table(
                ['Severity', 'Path', 'Message'],
                array_map(static fn (array $issue): array => [
                    $issue['severity'],
                    $issue['path'],
                    $issue['message'],
                ], $report->issues),
            );
        }

        if (null === $writePs1) {
            $io->note('Use --write-ps1=delivery/patches/apply_owner_configuration_external_package_overlay.ps1 to write the script.');
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-issues'));
    }

    /** @param array<string, mixed> $payload */
    private function buildReport(string $overlayPlanPath, array $payload, ?string $componentFilter): AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport
    {
        $issues = [];
        $componentPlans = [];
        $planAccepted = self::EXPECTED_SCHEMA === ($payload['schema'] ?? null);

        if (!$planAccepted) {
            $issues[] = $this->issue('error', 'schema', sprintf('Expected schema %s.', self::EXPECTED_SCHEMA));
        }

        $this->requireLiteral($payload, 'deliveryMode', 'overlay_only', $issues);
        $this->requireLiteral($payload, 'deleteMode', 'none', $issues);
        $this->requireLiteral($payload, 'automaticMoveAllowed', false, $issues);

        $plans = $payload['componentPlans'] ?? null;
        if (!is_array($plans)) {
            $issues[] = $this->issue('error', 'componentPlans', 'componentPlans must be an array.');

            return new AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport($overlayPlanPath, $planAccepted, $componentPlans, $issues);
        }

        foreach ($plans as $index => $plan) {
            $planPath = sprintf('componentPlans[%d]', $index);
            if (!is_array($plan)) {
                $issues[] = $this->issue('error', $planPath, 'Component plan must be an object.');
                continue;
            }

            $componentKey = $this->stringValue($plan['componentKey'] ?? null) ?? 'Unknown';
            $componentToken = $this->stringValue($plan['componentToken'] ?? null) ?? strtolower($componentKey);
            if (!$this->matchesComponentFilter($componentKey, $componentToken, $componentFilter)) {
                continue;
            }

            $this->requireLiteral($plan, 'deliveryMode', 'overlay_only', $issues, $planPath.'.deliveryMode');
            $this->requireLiteral($plan, 'deleteMode', 'none', $issues, $planPath.'.deleteMode');
            $this->requireLiteral($plan, 'automaticMoveAllowed', false, $issues, $planPath.'.automaticMoveAllowed');

            $overlayFiles = [];
            $files = is_array($plan['overlayFiles'] ?? null) ? $plan['overlayFiles'] : [];
            foreach ($files as $fileIndex => $file) {
                $filePath = sprintf('%s.overlayFiles[%d]', $planPath, $fileIndex);
                if (!is_array($file)) {
                    $issues[] = $this->issue('error', $filePath, 'Overlay file entry must be an object.');
                    continue;
                }

                $packagePath = $this->stringValue($file['path'] ?? null);
                if (null === $packagePath || !$this->isSafeRelativePath($packagePath)) {
                    $issues[] = $this->issue('error', $filePath.'.path', 'Overlay file path must be safe and repository-relative.');
                    continue;
                }

                $targetRelativePath = $this->targetRelativePath($packagePath, $componentKey, $componentToken);
                if (null === $targetRelativePath) {
                    $issues[] = $this->issue('error', $filePath.'.path', 'Overlay file path must start with the component package root.');
                    continue;
                }

                $overlayFiles[] = [
                    'packagePath' => $packagePath,
                    'targetRelativePath' => $targetRelativePath,
                    'kind' => $this->stringValue($file['kind'] ?? null) ?? 'support',
                    'copyMode' => 'overlay_only',
                    'deleteMode' => 'none',
                    'automaticMoveAllowed' => false,
                ];
            }

            $componentPlans[] = [
                'componentKey' => $componentKey,
                'componentToken' => $componentToken,
                'defaultRepositoryRoot' => sprintf('$WorkspaceRoot\%s', $componentKey),
                'overlayFiles' => $overlayFiles,
                'overlayFileCount' => count($overlayFiles),
                'deliveryMode' => 'overlay_only',
                'deleteMode' => 'none',
                'automaticMoveAllowed' => false,
                'manualReviewRequired' => true,
            ];
        }

        return new AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport($overlayPlanPath, $planAccepted, $componentPlans, $issues);
    }

    private function buildPowerShellScript(AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport $report): string
    {
        $payload = json_encode($report->componentPlans, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PS1
param(
    [Parameter(Mandatory = \$true)]
    [string]\$PackageRoot,

    [Parameter(Mandatory = \$true)]
    [string]\$WorkspaceRoot,

    [string]\$RepositoryMapJson = '',

    [switch]\$WhatIfOnly
)

\$ErrorActionPreference = 'Stop'
\$componentPlansJson = @'
{$payload}
'@

\$componentPlans = \$componentPlansJson | ConvertFrom-Json -Depth 20
\$repositoryMap = @{}
if (\$RepositoryMapJson -and (Test-Path \$RepositoryMapJson)) {
    \$mapObject = Get-Content \$RepositoryMapJson -Raw | ConvertFrom-Json -Depth 20
    foreach (\$property in \$mapObject.PSObject.Properties) {
        \$repositoryMap[\$property.Name] = [string]\$property.Value
    }
}

function Resolve-OwnerRepositoryRoot {
    param(
        [Parameter(Mandatory = \$true)] [string]\$ComponentKey,
        [Parameter(Mandatory = \$true)] [string]\$ComponentToken
    )

    if (\$repositoryMap.ContainsKey(\$ComponentKey)) {
        return \$repositoryMap[\$ComponentKey]
    }

    if (\$repositoryMap.ContainsKey(\$ComponentToken)) {
        return \$repositoryMap[\$ComponentToken]
    }

    return (Join-Path \$WorkspaceRoot \$ComponentKey)
}

foreach (\$componentPlan in \$componentPlans) {
    \$repositoryRoot = Resolve-OwnerRepositoryRoot -ComponentKey \$componentPlan.componentKey -ComponentToken \$componentPlan.componentToken
    if (-not (Test-Path \$repositoryRoot)) {
        Write-Warning "Repository root not found for component \$(\$componentPlan.componentKey): \$repositoryRoot"
        continue
    }

    foreach (\$file in \$componentPlan.overlayFiles) {
        \$sourcePath = Join-Path \$PackageRoot \$file.packagePath
        \$targetPath = Join-Path \$repositoryRoot \$file.targetRelativePath

        if (-not (Test-Path \$sourcePath)) {
            Write-Warning "Package source missing: \$sourcePath"
            continue
        }

        \$targetDirectory = Split-Path \$targetPath -Parent
        if (\$WhatIfOnly) {
            Write-Host "WHATIF overlay \$sourcePath -> \$targetPath" -ForegroundColor Cyan
            continue
        }

        if (-not (Test-Path \$targetDirectory)) {
            New-Item -ItemType Directory -Path \$targetDirectory -Force | Out-Null
        }

        Copy-Item -Path \$sourcePath -Destination \$targetPath -Force
        Write-Host "Overlayed \$targetPath" -ForegroundColor Green
    }
}

Write-Host 'Owner-side external package overlay completed. No files were deleted.' -ForegroundColor Green
PS1;
    }

    private function targetRelativePath(string $packagePath, string $componentKey, string $componentToken): ?string
    {
        foreach ([$componentKey, ucfirst($componentToken), $componentToken] as $prefix) {
            $prefix = trim($prefix, '/');
            if (str_starts_with($packagePath, $prefix.'/')) {
                return substr($packagePath, strlen($prefix) + 1);
            }
        }

        return null;
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

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageApplyScriptReport $report, bool $allowEmpty, bool $allowIssues): int
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
}
