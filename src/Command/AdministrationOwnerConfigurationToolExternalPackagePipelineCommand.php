<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackagePipelineReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:external-package-pipeline',
    description: 'Runs the non-destructive owner-side external handoff generation and validation pipeline.',
)]
final class AdministrationOwnerConfigurationToolExternalPackagePipelineCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('output-root', null, InputOption::VALUE_REQUIRED, 'Directory for generated pipeline artifacts.', 'delivery/patches')
            ->addOption('handoff-dir', null, InputOption::VALUE_REQUIRED, 'Directory where the generated handoff bundle should be written.', 'delivery/owner-side-external-handoff')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print pipeline report as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write pipeline report JSON to this path.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Pass allow-empty through all transition-stage commands.')
            ->addOption('allow-rejected', null, InputOption::VALUE_NONE, 'Pass allow-rejected to manifest generation.')
            ->addOption('allow-warnings', null, InputOption::VALUE_NONE, 'Pass allow-warnings to validation commands.')
            ->addOption('allow-issues', null, InputOption::VALUE_NONE, 'Pass allow-issues to overlay/apply/handoff commands.')
            ->addOption('continue-on-failure', null, InputOption::VALUE_NONE, 'Continue running later steps after a failed step and report all failures.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = $this->getApplication();
        if (null === $application) {
            $io->error('Console application is not available for pipeline command dispatch.');

            return Command::FAILURE;
        }

        $outputRoot = rtrim((string) $input->getOption('output-root'), DIRECTORY_SEPARATOR);
        $handoffDir = rtrim((string) $input->getOption('handoff-dir'), DIRECTORY_SEPARATOR);
        $allowEmpty = (bool) $input->getOption('allow-empty');
        $allowRejected = (bool) $input->getOption('allow-rejected');
        $allowWarnings = (bool) $input->getOption('allow-warnings');
        $allowIssues = (bool) $input->getOption('allow-issues');
        $continueOnFailure = (bool) $input->getOption('continue-on-failure');

        if (!is_dir($outputRoot) && !mkdir($outputRoot, 0775, true) && !is_dir($outputRoot)) {
            $io->error(sprintf('Unable to create output root: %s', $outputRoot));

            return Command::FAILURE;
        }

        $manifestPath = $outputRoot.'/administering_owner_configuration_tool_external_package_manifest.json';
        $manifestValidationPath = $outputRoot.'/administering_owner_configuration_tool_external_package_manifest_validation.json';
        $overlayPlanPath = $outputRoot.'/administering_owner_configuration_tool_external_package_overlay_plan.json';
        $applyScriptPath = $outputRoot.'/apply_owner_configuration_external_package_overlay.ps1';
        $handoffValidationPath = $outputRoot.'/administering_owner_configuration_tool_external_handoff_bundle_validation.json';

        $steps = [];
        $issues = [];

        $pipeline = [
            [
                'step' => 'manifest',
                'command' => 'administering:owner-configuration-tools:external-package-manifest',
                'outputPath' => $manifestPath,
                'arguments' => [
                    '--write-json' => $manifestPath,
                    '--allow-empty' => $allowEmpty,
                    '--allow-rejected' => $allowRejected,
                ],
            ],
            [
                'step' => 'manifest_validation',
                'command' => 'administering:owner-configuration-tools:external-package-manifest:validate',
                'outputPath' => $manifestValidationPath,
                'arguments' => [
                    'manifest' => $manifestPath,
                    '--write-json' => $manifestValidationPath,
                    '--allow-warnings' => $allowWarnings,
                ],
            ],
            [
                'step' => 'overlay_plan',
                'command' => 'administering:owner-configuration-tools:external-package-overlay-plan',
                'outputPath' => $overlayPlanPath,
                'arguments' => [
                    'manifest' => $manifestPath,
                    '--write-json' => $overlayPlanPath,
                    '--allow-empty' => $allowEmpty,
                    '--allow-issues' => $allowIssues,
                ],
            ],
            [
                'step' => 'apply_script',
                'command' => 'administering:owner-configuration-tools:external-package-apply-script',
                'outputPath' => $applyScriptPath,
                'arguments' => [
                    'overlay-plan' => $overlayPlanPath,
                    '--write-ps1' => $applyScriptPath,
                    '--allow-empty' => $allowEmpty,
                    '--allow-issues' => $allowIssues,
                ],
            ],
            [
                'step' => 'handoff_bundle',
                'command' => 'administering:owner-configuration-tools:external-package-handoff-bundle',
                'outputPath' => $handoffDir,
                'arguments' => [
                    'overlay-plan' => $overlayPlanPath,
                    '--manifest' => $manifestPath,
                    '--validation' => $manifestValidationPath,
                    '--apply-script' => $applyScriptPath,
                    '--output-dir' => $handoffDir,
                    '--allow-empty' => $allowEmpty,
                    '--allow-issues' => $allowIssues,
                ],
            ],
            [
                'step' => 'handoff_bundle_validation',
                'command' => 'administering:owner-configuration-tools:external-package-handoff-bundle:validate',
                'outputPath' => $handoffValidationPath,
                'arguments' => [
                    'handoff-dir' => $handoffDir,
                    '--write-json' => $handoffValidationPath,
                    '--allow-empty' => $allowEmpty,
                    '--allow-warnings' => $allowWarnings,
                ],
            ],
        ];

        foreach ($pipeline as $definition) {
            $stepName = $definition['step'];
            $commandName = $definition['command'];
            $io->section(sprintf('Running owner external package pipeline step: %s', $stepName));

            $command = $application->find($commandName);
            $stepInput = new ArrayInput(['command' => $commandName] + $this->withoutFalseOptions($definition['arguments']));
            $stepInput->setInteractive(false);
            $bufferedOutput = new BufferedOutput($output->getVerbosity(), false);
            $exitCode = $command->run($stepInput, $bufferedOutput);
            $status = 0 === $exitCode ? 'ok' : 'failed';

            $steps[] = [
                'step' => $stepName,
                'command' => $commandName,
                'status' => $status,
                'exitCode' => $exitCode,
                'outputPath' => $definition['outputPath'],
            ];

            $stepOutput = trim($bufferedOutput->fetch());
            if ('' !== $stepOutput && $output->isVerbose()) {
                $io->writeln($stepOutput);
            }

            if (0 !== $exitCode) {
                $issues[] = [
                    'severity' => 'error',
                    'path' => $stepName,
                    'message' => sprintf('Pipeline step %s failed with exit code %d.', $stepName, $exitCode),
                ];

                if (!$continueOnFailure) {
                    break;
                }
            }
        }

        $report = new AdministrationOwnerConfigurationToolExternalPackagePipelineReport($outputRoot, $handoffDir, $steps, $issues);
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                throw new \RuntimeException(sprintf('Unable to create pipeline report directory: %s', $targetDirectory));
            }
            file_put_contents($writeJson, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side external package pipeline report written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report->hasErrors() ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner-side external package pipeline');
        $io->writeln(sprintf('Output root: <info>%s</info>', $report->outputRoot));
        $io->writeln(sprintf('Handoff dir: <info>%s</info>', $report->handoffDir));
        $io->writeln(sprintf('Steps: <info>%d</info>', $report->stepCount()));
        $io->writeln(sprintf('Failed steps: <comment>%d</comment>', $report->failedStepCount()));
        $io->writeln(sprintf('Errors: <comment>%d</comment>', $report->errorCount()));

        $io->table(
            ['Step', 'Command', 'Status', 'Output path'],
            array_map(static fn (array $step): array => [
                $step['step'],
                $step['command'],
                $step['status'],
                $step['outputPath'] ?? '',
            ], $report->steps),
        );

        $io->note('Generated apply script remains non-destructive. Run it with -WhatIfOnly before any overlay into neighboring repositories.');

        return $report->hasErrors() ? Command::FAILURE : Command::SUCCESS;
    }

    /** @param array<string, mixed> $arguments @return array<string, mixed> */
    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function withoutFalseOptions(array $arguments): array
    {
        $filtered = [];
        foreach ($arguments as $key => $value) {
            if (false === $value || null === $value) {
                continue;
            }
            $filtered[$key] = $value;
        }

        return $filtered;
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
