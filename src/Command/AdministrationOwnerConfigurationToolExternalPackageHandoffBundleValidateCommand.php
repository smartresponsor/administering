<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidationReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:external-package-handoff-bundle:validate',
    description: 'Validates a generated owner-side external handoff bundle without applying it.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidateCommand extends Command
{
    private const EXPECTED_SCHEMA = 'smart-responsor.administering.owner_configuration_external_package_handoff_bundle.v1';

    protected function configure(): void
    {
        $this
            ->addArgument('handoff-dir', InputArgument::REQUIRED, 'Path to generated owner-side external handoff directory.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write validation report JSON to this path.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print validation report as JSON.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when the handoff bundle contains no component plans.')
            ->addOption('allow-warnings', null, InputOption::VALUE_NONE, 'Do not fail when warnings are present.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $handoffDir = rtrim((string) $input->getArgument('handoff-dir'), DIRECTORY_SEPARATOR);
        $report = $this->validateHandoffDir($handoffDir);

        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $dir = dirname($writeJson);
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Unable to create output directory: %s', $dir));
            }
            file_put_contents($writeJson, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Handoff bundle validation report written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-warnings'));
        }

        $io->section('Owner-side external handoff bundle validation');
        $io->writeln(sprintf('Handoff dir: <info>%s</info>', $report->handoffDir));
        $io->writeln(sprintf('README.adoc: <info>%s</info>', $report->readmeExists ? 'yes' : 'no'));
        $io->writeln(sprintf('CHECKLIST.adoc: <info>%s</info>', $report->checklistExists ? 'yes' : 'no'));
        $io->writeln(sprintf('handoff-report.json: <info>%s</info>', $report->reportExists ? 'yes' : 'no'));
        $io->writeln(sprintf('Safety contract accepted: <info>%s</info>', $report->safetyContractAccepted ? 'yes' : 'no'));
        $io->writeln(sprintf('Components: <info>%d</info>', $report->componentCount));
        $io->writeln(sprintf('Overlay files: <info>%d</info>', $report->fileCount));
        $io->writeln(sprintf('Errors: <comment>%d</comment>', $report->errorCount()));
        $io->writeln(sprintf('Warnings: <comment>%d</comment>', $report->warningCount()));

        if ([] !== $report->issues) {
            $io->section('Validation issues');
            $io->table(
                ['Severity', 'Path', 'Message'],
                array_map(static fn (array $issue): array => [
                    $issue['severity'],
                    $issue['path'],
                    $issue['message'],
                ], $report->issues),
            );
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-warnings'));
    }

    private function validateHandoffDir(string $handoffDir): AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidationReport
    {
        $issues = [];
        if (!is_dir($handoffDir)) {
            $issues[] = $this->issue('error', 'handoffDir', sprintf('Handoff directory does not exist: %s', $handoffDir));

            return new AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidationReport($handoffDir, false, false, false, false, 0, 0, $issues);
        }

        $readmePath = $handoffDir.'/README.adoc';
        $checklistPath = $handoffDir.'/CHECKLIST.adoc';
        $reportPath = $handoffDir.'/handoff-report.json';
        $readmeExists = is_file($readmePath);
        $checklistExists = is_file($checklistPath);
        $reportExists = is_file($reportPath);

        if (!$readmeExists) {
            $issues[] = $this->issue('error', 'README.adoc', 'README.adoc is missing from handoff bundle.');
        }
        if (!$checklistExists) {
            $issues[] = $this->issue('error', 'CHECKLIST.adoc', 'CHECKLIST.adoc is missing from handoff bundle.');
        }
        if (!$reportExists) {
            $issues[] = $this->issue('error', 'handoff-report.json', 'handoff-report.json is missing from handoff bundle.');
        }

        $payload = [];
        if ($reportExists) {
            try {
                $decoded = json_decode((string) file_get_contents($reportPath), true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $payload = $decoded;
                } else {
                    $issues[] = $this->issue('error', 'handoff-report.json', 'handoff-report.json root must be an object.');
                }
            } catch (\JsonException $exception) {
                $issues[] = $this->issue('error', 'handoff-report.json', sprintf('Invalid JSON: %s', $exception->getMessage()));
            }
        }

        $safetyContractAccepted = $this->validatePayload($payload, $issues);

        if ($readmeExists) {
            $this->requireText($readmePath, 'overlay_only', $issues);
            $this->requireText($readmePath, 'Delete mode: `none`', $issues);
            $this->requireText($readmePath, 'Automatic moves are forbidden', $issues);
        }
        if ($checklistExists) {
            $this->requireText($checklistPath, 'Run generated apply script with `-WhatIfOnly` first', $issues);
            $this->requireText($checklistPath, 'Confirm no generated step deletes files', $issues);
        }

        return new AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidationReport(
            $handoffDir,
            $readmeExists,
            $checklistExists,
            $reportExists,
            $safetyContractAccepted,
            (int) ($payload['componentCount'] ?? 0),
            (int) ($payload['fileCount'] ?? 0),
            $issues,
        );
    }

    /** @param array<string, mixed> $payload @param list<array<string, string>> $issues */
    /**
     * @param array<string, mixed>        $payload
     * @param list<array<string, string>> $issues
     */
    private function validatePayload(array $payload, array &$issues): bool
    {
        if ([] === $payload) {
            return false;
        }

        $accepted = true;
        $expected = [
            'schema' => self::EXPECTED_SCHEMA,
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'manualReviewRequired' => true,
        ];

        foreach ($expected as $key => $value) {
            if (($payload[$key] ?? null) !== $value) {
                $accepted = false;
                $issues[] = $this->issue('error', $key, sprintf('Expected literal %s.', json_encode($value, JSON_THROW_ON_ERROR)));
            }
        }

        $componentPlans = $payload['componentPlans'] ?? [];
        if (!is_array($componentPlans)) {
            $accepted = false;
            $issues[] = $this->issue('error', 'componentPlans', 'componentPlans must be an array.');

            return false;
        }

        foreach ($componentPlans as $index => $plan) {
            $path = sprintf('componentPlans[%d]', $index);
            if (!is_array($plan)) {
                $accepted = false;
                $issues[] = $this->issue('error', $path, 'Component plan must be an object.');
                continue;
            }
            if (($plan['deliveryMode'] ?? 'overlay_only') !== 'overlay_only') {
                $accepted = false;
                $issues[] = $this->issue('error', $path.'.deliveryMode', 'Component deliveryMode must be overlay_only.');
            }
            if (($plan['deleteMode'] ?? 'none') !== 'none') {
                $accepted = false;
                $issues[] = $this->issue('error', $path.'.deleteMode', 'Component deleteMode must be none.');
            }
            if (($plan['automaticMoveAllowed'] ?? false) !== false) {
                $accepted = false;
                $issues[] = $this->issue('error', $path.'.automaticMoveAllowed', 'automaticMoveAllowed must be false.');
            }
        }

        return $accepted;
    }

    /**
     * @param list<array<string, string>> $issues
     */
    private function requireText(string $path, string $needle, array &$issues): void
    {
        $content = (string) file_get_contents($path);
        if (!str_contains($content, $needle)) {
            $issues[] = $this->issue('warning', basename($path), sprintf('Expected safety text not found: %s', $needle));
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

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageHandoffBundleValidationReport $report, bool $allowEmpty, bool $allowWarnings): int
    {
        if (0 === $report->componentCount && !$allowEmpty) {
            return Command::FAILURE;
        }
        if ($report->hasErrors()) {
            return Command::FAILURE;
        }
        if (0 < $report->warningCount() && !$allowWarnings) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
