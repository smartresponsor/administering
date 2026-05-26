<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolTransitionPauseGateReport;
use App\Administering\Value\Admin\AdministrationServiceTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:transition-pause-gate',
    description: 'Reports whether Administering should pause internal waves and move the next work to owner/host current slices.',
)]
final class AdministrationOwnerConfigurationToolTransitionPauseGateCommand extends Command
{
    public function __construct(
        private readonly AdministrationServiceToolCatalogInterface $toolCatalog,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional component/section key or token, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print pause-gate report as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write pause-gate report JSON to this path.')
            ->addOption('fail-if-not-ready', null, InputOption::VALUE_NONE, 'Fail when Administering should not yet pause internal transition waves.')
            ->addOption('pipeline-report', null, InputOption::VALUE_REQUIRED, 'External package pipeline report path.', 'delivery/patches/administering_owner_configuration_tool_external_package_pipeline.json')
            ->addOption('handoff-dir', null, InputOption::VALUE_REQUIRED, 'External handoff bundle directory.', 'delivery/owner-side-external-handoff')
            ->addOption('handoff-validation', null, InputOption::VALUE_REQUIRED, 'External handoff bundle validation report path.', 'delivery/patches/administering_owner_configuration_tool_external_handoff_bundle_validation.json')
            ->addOption('transition-decision', null, InputOption::VALUE_REQUIRED, 'Transition decision report path.', 'delivery/patches/administering_owner_configuration_tool_transition_decision.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));

        $pipelineReportPath = $this->projectPath((string) $input->getOption('pipeline-report'));
        $handoffDir = $this->projectPath((string) $input->getOption('handoff-dir'));
        $handoffValidationPath = $this->projectPath((string) $input->getOption('handoff-validation'));
        $transitionDecisionPath = $this->projectPath((string) $input->getOption('transition-decision'));

        $externalPipelineReportPresent = is_file($pipelineReportPath);
        $handoffBundlePresent = is_dir($handoffDir)
            && is_file($handoffDir.'/README.adoc')
            && is_file($handoffDir.'/CHECKLIST.adoc')
            && is_file($handoffDir.'/handoff-report.json');
        $handoffBundleValidationPresent = is_file($handoffValidationPath);
        $transitionDecisionReportPresent = is_file($transitionDecisionPath);

        $classificationCounts = [];
        $classifications = [];

        foreach ($this->toolCatalog->tools() as $tool) {
            if (!$this->matchesToolFilter($tool, $componentFilter)) {
                continue;
            }

            $classification = $this->classify($tool);
            $classificationCounts[$classification] = ($classificationCounts[$classification] ?? 0) + 1;
            $classifications[] = [
                'classification' => $classification,
                'section' => $tool->section,
                'toolKey' => $tool->toolKey,
                'toolSlug' => $tool->toolSlug,
                'sourceOwnership' => $tool->sourceOwnership,
                'serviceClass' => $tool->serviceClass,
                'ownerComponentKey' => $tool->ownerComponentKey,
                'ownerComponentToken' => $tool->ownerComponentToken,
                'recommendedNextTarget' => $this->recommendedNextTarget($tool, $classification),
                'recommendedAction' => $this->recommendedAction($classification),
            ];
        }

        ksort($classificationCounts);
        usort($classifications, static fn (array $left, array $right): int => [$left['classification'], $left['section'], $left['toolKey']] <=> [$right['classification'], $right['section'], $right['toolKey']]);

        $issues = $this->issues(
            $externalPipelineReportPresent,
            $handoffBundlePresent,
            $handoffBundleValidationPresent,
            $transitionDecisionReportPresent,
        );

        $report = new AdministrationOwnerConfigurationToolTransitionPauseGateReport(
            $componentFilter,
            $classificationCounts,
            $classifications,
            $issues,
            $this->recommendedNextActions($classificationCounts, $externalPipelineReportPresent, $handoffBundlePresent, $handoffBundleValidationPresent),
            $externalPipelineReportPresent,
            $handoffBundlePresent,
            $handoffBundleValidationPresent,
            $transitionDecisionReportPresent,
        );

        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetPath = $this->projectPath($writeJson);
            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create pause-gate report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($targetPath, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side transition pause-gate report written to %s.', $targetPath));
        }

        $shouldFail = (bool) $input->getOption('fail-if-not-ready') && !$report->canPauseInternalWaves();
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $shouldFail ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner-side transition pause gate');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Tools: <info>%d</info>', $report->toolCount()));
        $io->writeln(sprintf('Owner-provided: <info>%d</info>', $report->ownerProvidedCount()));
        $io->writeln(sprintf('Admin shell-owned: <info>%d</info>', $report->adminShellOwnedCount()));
        $io->writeln(sprintf('Owner repository candidates: <comment>%d</comment>', $report->ownerRepositoryCandidateCount()));
        $io->writeln(sprintf('Host/post-application candidates: <comment>%d</comment>', $report->hostApplicationCandidateCount()));
        $io->writeln(sprintf('External pipeline report: <info>%s</info>', $externalPipelineReportPresent ? 'present' : 'missing'));
        $io->writeln(sprintf('Handoff bundle: <info>%s</info>', $handoffBundlePresent ? 'present' : 'missing'));
        $io->writeln(sprintf('Handoff bundle validation: <info>%s</info>', $handoffBundleValidationPresent ? 'present' : 'missing'));
        $io->writeln(sprintf('Transition decision report: <info>%s</info>', $transitionDecisionReportPresent ? 'present' : 'missing'));
        $io->writeln(sprintf('Can pause internal waves: <info>%s</info>', $report->canPauseInternalWaves() ? 'yes' : 'not yet'));
        $io->writeln(sprintf('Next work mode: <info>%s</info>', $report->nextWorkMode()));

        if ([] !== $classifications) {
            $io->table(
                ['Classification', 'Section', 'Tool key', 'Source', 'Recommended target', 'Recommended action'],
                array_map(static fn (array $row): array => [
                    $row['classification'],
                    $row['section'],
                    $row['toolKey'],
                    $row['sourceOwnership'],
                    $row['recommendedNextTarget'] ?? '-',
                    $row['recommendedAction'],
                ], $classifications),
            );
        }

        if ([] !== $report->recommendedNextActions) {
            $io->section('Recommended next actions');
            foreach ($report->recommendedNextActions as $action) {
                $io->writeln('- '.$action);
            }
        }

        if (0 < $report->warningCount()) {
            $io->warning(sprintf('%d pause-gate warning(s) found.', $report->warningCount()));
        }

        return $shouldFail ? Command::FAILURE : Command::SUCCESS;
    }

    private function classify(AdministrationServiceTool $tool): string
    {
        if ('owner_component' === $tool->sourceOwnership) {
            return 'owner_provided';
        }

        if (in_array($tool->section, ['Admin', 'Operation'], true)) {
            return 'admin_shell_owned';
        }

        if (in_array($tool->section, ['Configuration', 'Credential', 'Environment', 'Symfony'], true)) {
            return 'host_application_candidate';
        }

        return 'owner_repository_candidate';
    }

    private function recommendedAction(string $classification): string
    {
        return match ($classification) {
            'owner_provided' => 'Keep owner provider materialized into SQLite projection.',
            'admin_shell_owned' => 'Keep in Administering as orchestration/governance shell.',
            'host_application_candidate' => 'Move only after host/post-application current slice is available.',
            default => 'Move only after matching owner repository current slice is available.',
        };
    }

    private function recommendedNextTarget(AdministrationServiceTool $tool, string $classification): ?string
    {
        if ('owner_repository_candidate' === $classification) {
            return sprintf('%s/src/Service/Configuration/%sConfiguration%sService.php', $tool->section, $tool->section, $tool->toolSlug);
        }

        if ('host_application_candidate' === $classification) {
            return sprintf('HostApp/configuration/%s/%s', strtolower($tool->section), $tool->toolKey);
        }

        return null;
    }

    /**
     * @return list<array{severity:string, code:string, message:string}>
     */
    private function issues(bool $externalPipelineReportPresent, bool $handoffBundlePresent, bool $handoffBundleValidationPresent, bool $transitionDecisionReportPresent): array
    {
        $issues = [];
        if (!$externalPipelineReportPresent) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'external_pipeline_report_missing',
                'message' => 'External package pipeline report is missing. Run external-package-pipeline before pausing internal transition waves.',
            ];
        }

        if (!$handoffBundlePresent) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'handoff_bundle_missing',
                'message' => 'External handoff bundle is missing or incomplete.',
            ];
        }

        if (!$handoffBundleValidationPresent) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'handoff_bundle_validation_missing',
                'message' => 'External handoff bundle validation report is missing.',
            ];
        }

        if (!$transitionDecisionReportPresent) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'transition_decision_report_missing',
                'message' => 'Transition decision report is missing. Generate it for a complete owner-side transition record.',
            ];
        }

        return $issues;
    }

    /**
     * @param array<string, int> $classificationCounts
     *
     * @return list<string>
     */
    private function recommendedNextActions(array $classificationCounts, bool $externalPipelineReportPresent, bool $handoffBundlePresent, bool $handoffBundleValidationPresent): array
    {
        $actions = [];
        if (!$externalPipelineReportPresent || !$handoffBundlePresent || !$handoffBundleValidationPresent) {
            $actions[] = 'Run the full external package pipeline and validate the generated handoff bundle.';
        }

        if (0 < ($classificationCounts['owner_repository_candidate'] ?? 0)) {
            $actions[] = 'Request current slices for owner repositories before generating real neighbor patches.';
        }

        if (0 < ($classificationCounts['host_application_candidate'] ?? 0)) {
            $actions[] = 'Request the host/post-application current slice before moving environment/Symfony/credential configuration tools.';
        }

        if ([] === $actions) {
            $actions[] = 'Pause internal Administering expansion and continue with owner/host repository current slices only.';
        }

        return $actions;
    }

    private function matchesToolFilter(AdministrationServiceTool $tool, ?string $filter): bool
    {
        if (null === $filter) {
            return true;
        }

        $normalizedFilter = strtolower($filter);

        return strtolower($tool->section) === $normalizedFilter
            || strtolower($tool->directionToken) === $normalizedFilter
            || strtolower((string) $tool->ownerComponentKey) === $normalizedFilter
            || strtolower((string) $tool->ownerComponentToken) === $normalizedFilter;
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function projectPath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return rtrim($this->projectDir, '/\\').'/'.ltrim($path, '/\\');
    }
}
