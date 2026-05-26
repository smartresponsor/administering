<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:external-package-handoff-bundle',
    description: 'Builds a non-destructive reviewed handoff bundle from owner-side external package artifacts.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageHandoffBundleCommand extends Command
{
    private const EXPECTED_OVERLAY_SCHEMA = 'smart-responsor.administering.owner_configuration_external_package_overlay_plan.v1';

    protected function configure(): void
    {
        $this
            ->addArgument('overlay-plan', InputArgument::REQUIRED, 'Path to owner external package overlay plan JSON.')
            ->addOption('manifest', null, InputOption::VALUE_REQUIRED, 'Optional external package manifest JSON path to reference in the handoff bundle.')
            ->addOption('validation', null, InputOption::VALUE_REQUIRED, 'Optional external package manifest validation JSON path to reference in the handoff bundle.')
            ->addOption('apply-script', null, InputOption::VALUE_REQUIRED, 'Optional generated PowerShell apply script path to reference in the handoff bundle.')
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'Write handoff README/checklist/report files to this directory.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print handoff bundle report as JSON.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when the overlay plan contains no component plans.')
            ->addOption('allow-issues', null, InputOption::VALUE_NONE, 'Do not fail when the handoff bundle report contains non-fatal issues.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $overlayPlanPath = (string) $input->getArgument('overlay-plan');
        $manifestPath = $this->normalizeOptionalString($input->getOption('manifest'));
        $validationPath = $this->normalizeOptionalString($input->getOption('validation'));
        $applyScriptPath = $this->normalizeOptionalString($input->getOption('apply-script'));
        $outputDir = $this->normalizeOptionalString($input->getOption('output-dir'));

        if (!is_file($overlayPlanPath)) {
            $io->error(sprintf('External package overlay plan not found: %s', $overlayPlanPath));

            return Command::FAILURE;
        }

        try {
            $overlayPayload = json_decode((string) file_get_contents($overlayPlanPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $io->error(sprintf('External package overlay plan is not valid JSON: %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        if (!is_array($overlayPayload)) {
            $io->error('External package overlay plan root must be a JSON object.');

            return Command::FAILURE;
        }

        $report = $this->buildReport($overlayPlanPath, $overlayPayload, $manifestPath, $validationPath, $applyScriptPath);

        if (null !== $outputDir) {
            $this->writeHandoffFiles($outputDir, $report);
            $io->success(sprintf('Owner-side external package handoff bundle written to %s.', $outputDir));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-issues'));
        }

        $io->section('Owner-side external package handoff bundle');
        $io->writeln(sprintf('Overlay plan: <info>%s</info>', $overlayPlanPath));
        $io->writeln(sprintf('Manifest: <info>%s</info>', $manifestPath ?? 'not provided'));
        $io->writeln(sprintf('Validation: <info>%s</info>', $validationPath ?? 'not provided'));
        $io->writeln(sprintf('Apply script: <info>%s</info>', $applyScriptPath ?? 'not provided'));
        $io->writeln(sprintf('Plan accepted: <info>%s</info>', $report->planAccepted ? 'yes' : 'no'));
        $io->writeln(sprintf('Components: <info>%d</info>', $report->componentCount()));
        $io->writeln(sprintf('Overlay files: <info>%d</info>', $report->fileCount()));
        $io->writeln(sprintf('Errors: <comment>%d</comment>', $report->errorCount()));
        $io->writeln(sprintf('Warnings: <comment>%d</comment>', $report->warningCount()));

        if ([] !== $report->componentPlans) {
            $io->table(
                ['Component', 'Token', 'Files', 'Delivery', 'Delete mode'],
                array_map(static fn (array $plan): array => [
                    $plan['componentKey'],
                    $plan['componentToken'],
                    (string) ($plan['overlayFileCount'] ?? count($plan['overlayFiles'] ?? [])),
                    $plan['deliveryMode'] ?? 'overlay_only',
                    $plan['deleteMode'] ?? 'none',
                ], $report->componentPlans),
            );
        }

        if ([] !== $report->issues) {
            $io->section('Handoff issues');
            $io->table(
                ['Severity', 'Path', 'Message'],
                array_map(static fn (array $issue): array => [
                    $issue['severity'],
                    $issue['path'],
                    $issue['message'],
                ], $report->issues),
            );
        }

        if (null === $outputDir) {
            $io->note('Use --output-dir=delivery/owner-handoff to write README.adoc, checklist, and JSON report files.');
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-issues'));
    }

    /** @param array<string, mixed> $payload */
    private function buildReport(string $overlayPlanPath, array $payload, ?string $manifestPath, ?string $validationPath, ?string $applyScriptPath): AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport
    {
        $issues = [];
        $planAccepted = self::EXPECTED_OVERLAY_SCHEMA === ($payload['schema'] ?? null);

        if (!$planAccepted) {
            $issues[] = $this->issue('error', 'schema', sprintf('Expected schema %s.', self::EXPECTED_OVERLAY_SCHEMA));
        }

        $this->requireLiteral($payload, 'deliveryMode', 'overlay_only', $issues);
        $this->requireLiteral($payload, 'deleteMode', 'none', $issues);
        $this->requireLiteral($payload, 'automaticMoveAllowed', false, $issues);

        $this->checkOptionalFile($manifestPath, 'manifest', $issues);
        $this->checkOptionalFile($validationPath, 'validation', $issues);
        $this->checkOptionalFile($applyScriptPath, 'applyScript', $issues);

        $componentPlans = [];
        $plans = $payload['componentPlans'] ?? [];
        if (!is_array($plans)) {
            $issues[] = $this->issue('error', 'componentPlans', 'componentPlans must be an array.');
            $plans = [];
        }

        foreach ($plans as $index => $plan) {
            $path = sprintf('componentPlans[%d]', $index);
            if (!is_array($plan)) {
                $issues[] = $this->issue('error', $path, 'Component plan must be an object.');
                continue;
            }

            $componentKey = $this->stringValue($plan['componentKey'] ?? null);
            $componentToken = $this->stringValue($plan['componentToken'] ?? null);
            if (null === $componentKey || null === $componentToken) {
                $issues[] = $this->issue('error', $path, 'Component plan must include componentKey and componentToken.');
                continue;
            }

            $overlayFiles = is_array($plan['overlayFiles'] ?? null) ? $plan['overlayFiles'] : [];
            $componentPlans[] = [
                'componentKey' => $componentKey,
                'componentToken' => $componentToken,
                'packageRoot' => $this->stringValue($plan['packageRoot'] ?? null) ?? $componentKey,
                'overlayFileCount' => count($overlayFiles),
                'overlayFiles' => $overlayFiles,
                'deliveryMode' => $this->stringValue($plan['deliveryMode'] ?? null) ?? 'overlay_only',
                'deleteMode' => $this->stringValue($plan['deleteMode'] ?? null) ?? 'none',
                'automaticMoveAllowed' => false,
                'manualReviewRequired' => true,
            ];
        }

        return new AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport(
            $overlayPlanPath,
            $manifestPath,
            $validationPath,
            $applyScriptPath,
            $planAccepted,
            $componentPlans,
            $issues,
        );
    }

    private function writeHandoffFiles(string $outputDir, AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport $report): void
    {
        if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            throw new \RuntimeException(sprintf('Unable to create handoff output directory: %s', $outputDir));
        }

        file_put_contents($outputDir.'/handoff-report.json', json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($outputDir.'/README.adoc', $this->buildReadme($report));
        file_put_contents($outputDir.'/CHECKLIST.adoc', $this->buildChecklist($report));
    }

    private function buildReadme(AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport $report): string
    {
        $components = [];
        foreach ($report->componentPlans as $plan) {
            $components[] = sprintf('| %s | %s | %d | overlay_only | none |', $plan['componentKey'], $plan['componentToken'], $plan['overlayFileCount']);
        }

        $rows = [] === $components ? '| _none_ | _none_ | 0 | overlay_only | none |' : implode("\n", $components);

        $manifestPath = $report->manifestPath ?? 'not provided';
        $validationPath = $report->validationPath ?? 'not provided';
        $applyScriptPath = $report->applyScriptPath ?? 'not provided';

        return <<<ADOC
= Owner-side Configuration Tool External Handoff

This directory is a reviewed handoff bundle for moving configuration tools toward owner-side repositories.

== Safety contract

* Delivery mode: `overlay_only`.
* Delete mode: `none`.
* Automatic moves are forbidden.
* Repository-wide cleanup is forbidden.
* Filesystem/tool identity remains owner-side.
* Administering remains the projection/orchestration shell.

== Source artifacts

* Overlay plan: `{$report->overlayPlanPath}`
* Manifest: `{$manifestPath}`
* Validation: `{$validationPath}`
* Apply script: `{$applyScriptPath}`

== Components

|===
| Component | Token | Overlay files | Delivery | Deletes
{$rows}
|===
ADOC;
    }

    private function buildChecklist(AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport $report): string
    {
        return <<<ADOC
= Owner-side External Handoff Checklist

== Before overlay

* [ ] Confirm each owner repository current slice is the active base.
* [ ] Review `handoff-report.json`.
* [ ] Run generated apply script with `-WhatIfOnly` first.
* [ ] Confirm no generated step deletes files.
* [ ] Confirm no generated step rewrites namespaces automatically.

== Overlay

* [ ] Apply only overlay files to each owner repository.
* [ ] Do not apply Administering cumulative archive as a patch.
* [ ] Keep old owner repository files until owner-specific review confirms replacements.

== After overlay

* [ ] Run PHP syntax checks in each touched owner repository.
* [ ] Register owner provider service/tag in each owner repository or host wiring.
* [ ] Run Administering discovery/validation/materialization preview.
* [ ] Run Administering refresh-index and index-readiness.

== Summary

* Components: {$report->componentCount()}
* Overlay files: {$report->fileCount()}
* Errors: {$report->errorCount()}
* Warnings: {$report->warningCount()}
ADOC;
    }

    /** @param list<array<string, string>> $issues */
    private function checkOptionalFile(?string $path, string $label, array &$issues): void
    {
        if (null !== $path && !is_file($path)) {
            $issues[] = $this->issue('warning', $label, sprintf('Referenced %s file does not exist: %s', $label, $path));
        }
    }

    /** @param array<string, mixed> $payload @param list<array<string, string>> $issues */
    private function requireLiteral(array $payload, string $key, mixed $expected, array &$issues): void
    {
        if (($payload[$key] ?? null) !== $expected) {
            $issues[] = $this->issue('error', $key, sprintf('Expected literal %s.', json_encode($expected, JSON_THROW_ON_ERROR)));
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

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageHandoffBundleReport $report, bool $allowEmpty, bool $allowIssues): int
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
