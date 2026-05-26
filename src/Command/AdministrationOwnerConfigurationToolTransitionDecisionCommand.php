<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolTransitionDecisionReport;
use App\Administering\Value\Admin\AdministrationServiceTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:transition-decision',
    description: 'Reports whether the owner-side transition track is ready to stop internal tool expansion and move to neighbor current slices.',
)]
final class AdministrationOwnerConfigurationToolTransitionDecisionCommand extends Command
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
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print transition decision as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write transition decision JSON to this path.')
            ->addOption('fail-if-not-ready', null, InputOption::VALUE_NONE, 'Fail when the transition is not ready to pause internal Administering waves.')
            ->addOption('pipeline-report', null, InputOption::VALUE_REQUIRED, 'Optional pipeline report path.', 'delivery/patches/administering_owner_configuration_tool_external_package_pipeline.json')
            ->addOption('handoff-dir', null, InputOption::VALUE_REQUIRED, 'Optional handoff bundle directory.', 'delivery/owner-side-external-handoff');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $pipelineReportPath = $this->projectPath((string) $input->getOption('pipeline-report'));
        $handoffDir = $this->projectPath((string) $input->getOption('handoff-dir'));

        $externalPipelinePresent = is_file($pipelineReportPath);
        $handoffBundlePresent = is_dir($handoffDir)
            && is_file($handoffDir.'/README.adoc')
            && is_file($handoffDir.'/CHECKLIST.adoc')
            && is_file($handoffDir.'/handoff-report.json');

        $tools = [];
        $decisionCounts = [];
        $issues = [];

        foreach ($this->toolCatalog->tools() as $tool) {
            if (!$this->matchesToolFilter($tool, $componentFilter)) {
                continue;
            }

            $decision = $this->decision($tool);
            $decisionCounts[$decision] = ($decisionCounts[$decision] ?? 0) + 1;
            $tools[] = [
                'decision' => $decision,
                'section' => $tool->section,
                'toolKey' => $tool->toolKey,
                'toolSlug' => $tool->toolSlug,
                'sourceOwnership' => $tool->sourceOwnership,
                'serviceClass' => $tool->serviceClass,
                'ownerComponentKey' => $tool->ownerComponentKey,
                'ownerComponentToken' => $tool->ownerComponentToken,
                'ownerProviderClass' => $tool->ownerProviderClass,
                'ownerServiceClass' => $tool->ownerServiceClass,
                'recommendedOwnerServicePath' => $this->recommendedOwnerServicePath($tool),
                'recommendedHostLayer' => $this->recommendedHostLayer($tool),
                'nextAction' => $this->nextAction($decision),
            ];
        }

        usort($tools, static fn (array $left, array $right): int => [$left['decision'], $left['section'], $left['toolKey']] <=> [$right['decision'], $right['section'], $right['toolKey']]);
        ksort($decisionCounts);

        if (!$externalPipelinePresent) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'external_pipeline_missing',
                'message' => 'External handoff pipeline report was not found. Run administering:owner-configuration-tools:external-package-pipeline before declaring transition readiness.',
            ];
        }

        if (!$handoffBundlePresent) {
            $issues[] = [
                'severity' => 'warning',
                'code' => 'handoff_bundle_missing',
                'message' => 'External handoff bundle was not found or is incomplete. Build and validate it before applying neighbor overlays.',
            ];
        }

        $recommendedNextActions = $this->recommendedNextActions($decisionCounts, $externalPipelinePresent, $handoffBundlePresent);
        $report = new AdministrationOwnerConfigurationToolTransitionDecisionReport(
            $componentFilter,
            $tools,
            $decisionCounts,
            $issues,
            $recommendedNextActions,
            $externalPipelinePresent,
            $handoffBundlePresent,
        );

        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetPath = $this->projectPath($writeJson);
            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create transition decision report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($targetPath, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side transition decision report written to %s.', $targetPath));
        }

        $shouldFail = (bool) $input->getOption('fail-if-not-ready') && !$report->canStopInternalExpansion();
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $shouldFail ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner-side transition decision');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Tools: <info>%d</info>', $report->toolCount()));
        $io->writeln(sprintf('Ready to pause internal waves: <info>%d</info>', $report->readyToPauseInternalWaveCount()));
        $io->writeln(sprintf('Needs owner repository current slice: <comment>%d</comment>', $report->needsOwnerRepositorySliceCount()));
        $io->writeln(sprintf('Needs host/post-application slice: <comment>%d</comment>', $report->needsHostApplicationSliceCount()));
        $io->writeln(sprintf('Keep in Administering shell: <info>%d</info>', $report->keepInAdministeringCount()));
        $io->writeln(sprintf('External pipeline report: <info>%s</info>', $externalPipelinePresent ? 'present' : 'missing'));
        $io->writeln(sprintf('External handoff bundle: <info>%s</info>', $handoffBundlePresent ? 'present' : 'missing'));
        $io->writeln(sprintf('Can stop internal expansion: <info>%s</info>', $report->canStopInternalExpansion() ? 'yes' : 'not yet'));

        if ([] !== $tools) {
            $io->table(
                ['Decision', 'Section', 'Tool key', 'Source', 'Owner path / host layer', 'Next action'],
                array_map(static fn (array $row): array => [
                    $row['decision'],
                    $row['section'],
                    $row['toolKey'],
                    $row['sourceOwnership'],
                    $row['recommendedOwnerServicePath'] ?? $row['recommendedHostLayer'] ?? '-',
                    $row['nextAction'],
                ], $tools),
            );
        }

        if ([] !== $recommendedNextActions) {
            $io->section('Recommended next actions');
            foreach ($recommendedNextActions as $action) {
                $io->writeln('- '.$action);
            }
        }

        if (0 < $report->warningCount()) {
            $io->warning(sprintf('%d transition warning(s) found.', $report->warningCount()));
        }

        return $shouldFail ? Command::FAILURE : Command::SUCCESS;
    }

    private function decision(AdministrationServiceTool $tool): string
    {
        if ('owner_component' === $tool->sourceOwnership) {
            return 'ready_to_pause_internal_waves';
        }

        if (in_array($tool->section, ['Admin', 'Operation'], true)) {
            return 'keep_in_administering_shell';
        }

        if (in_array($tool->section, ['Configuration', 'Credential', 'Environment', 'Symfony'], true)) {
            return 'needs_host_application_slice';
        }

        return 'needs_owner_repository_slice';
    }

    private function nextAction(string $decision): string
    {
        return match ($decision) {
            'ready_to_pause_internal_waves' => 'Keep materializing owner provider into SQLite projection.',
            'keep_in_administering_shell' => 'Keep in Administering as shell/governance infrastructure.',
            'needs_host_application_slice' => 'Wait for host/post-application current slice and move environment/Symfony config there.',
            default => 'Wait for matching owner repository current slice and apply reviewed external overlay.',
        };
    }

    private function recommendedOwnerServicePath(AdministrationServiceTool $tool): ?string
    {
        if ('needs_owner_repository_slice' !== $this->decision($tool)) {
            return null;
        }

        return $tool->section.'/src/Service/Configuration/'.$tool->section.'Configuration'.$tool->toolSlug.'Service.php';
    }

    private function recommendedHostLayer(AdministrationServiceTool $tool): ?string
    {
        if ('needs_host_application_slice' !== $this->decision($tool)) {
            return null;
        }

        return 'host-app/src/Service/Configuration/'.$tool->section.'Configuration'.$tool->toolSlug.'Service.php';
    }

    /** @param array<string, int> $decisionCounts */
    private function recommendedNextActions(array $decisionCounts, bool $externalPipelinePresent, bool $handoffBundlePresent): array
    {
        $actions = [];
        if (!$externalPipelinePresent || !$handoffBundlePresent) {
            $actions[] = 'Run the external handoff pipeline and validate the generated handoff bundle.';
        }

        if (0 < ($decisionCounts['needs_owner_repository_slice'] ?? 0)) {
            $actions[] = 'Request current slices for owner repositories that still have extraction candidates, then generate real neighbor touched overlays from the external kit.';
        }

        if (0 < ($decisionCounts['needs_host_application_slice'] ?? 0)) {
            $actions[] = 'Handle Symfony/environment/credential tools in the host/post-application configuration layer, not in owner components.';
        }

        if (0 === ($decisionCounts['needs_owner_repository_slice'] ?? 0) && 0 === ($decisionCounts['needs_host_application_slice'] ?? 0)) {
            $actions[] = 'Stop expanding internal ecosystem tools in Administering and keep only shell/governance/admin tools here.';
        }

        return $actions;
    }

    private function matchesToolFilter(AdministrationServiceTool $tool, ?string $componentFilter): bool
    {
        if (null === $componentFilter) {
            return true;
        }

        return 0 === strcasecmp($tool->section, $componentFilter)
            || 0 === strcasecmp($tool->ownerComponentKey ?? '', $componentFilter)
            || 0 === strcasecmp($tool->ownerComponentToken ?? '', $componentFilter);
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
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return rtrim($this->projectDir, '/\\').'/'.$path;
    }
}
