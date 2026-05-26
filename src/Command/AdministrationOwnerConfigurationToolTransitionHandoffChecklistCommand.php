<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolTransitionHandoffChecklistReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:transition-handoff-checklist',
    description: 'Builds the final checklist for switching from Administering internal transition waves to owner/host current-slice work.',
)]
final class AdministrationOwnerConfigurationToolTransitionHandoffChecklistCommand extends Command
{
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print checklist as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write checklist JSON to this path.')
            ->addOption('fail-if-not-ready', null, InputOption::VALUE_NONE, 'Fail when owner slice work should not start yet.')
            ->addOption('pipeline-report', null, InputOption::VALUE_REQUIRED, 'External package pipeline report path.', 'delivery/patches/administering_owner_configuration_tool_external_package_pipeline.json')
            ->addOption('handoff-dir', null, InputOption::VALUE_REQUIRED, 'External handoff bundle directory.', 'delivery/owner-side-external-handoff')
            ->addOption('handoff-validation', null, InputOption::VALUE_REQUIRED, 'External handoff bundle validation report path.', 'delivery/patches/administering_owner_configuration_tool_external_handoff_bundle_validation.json')
            ->addOption('transition-status', null, InputOption::VALUE_REQUIRED, 'Transition status report path.', 'delivery/patches/administering_owner_configuration_tool_transition_status.json')
            ->addOption('transition-decision', null, InputOption::VALUE_REQUIRED, 'Transition decision report path.', 'delivery/patches/administering_owner_configuration_tool_transition_decision.json')
            ->addOption('pause-gate', null, InputOption::VALUE_REQUIRED, 'Transition pause gate report path.', 'delivery/patches/administering_owner_configuration_tool_transition_pause_gate.json')
            ->addOption('host-slice-ready', null, InputOption::VALUE_NONE, 'Mark host/post-application current slice as available for the next work mode.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $checklist = [
            $this->artifact('pipeline_report', 'External package pipeline report', (string) $input->getOption('pipeline-report'), 'Run external-package-pipeline to build the full non-destructive handoff chain.'),
            $this->directoryArtifact('handoff_bundle', 'External handoff bundle directory', (string) $input->getOption('handoff-dir'), 'Bundle must contain README.adoc, CHECKLIST.adoc, and handoff-report.json.'),
            $this->artifact('handoff_validation', 'External handoff bundle validation report', (string) $input->getOption('handoff-validation'), 'Run external-package-handoff-bundle:validate.'),
            $this->artifact('transition_status', 'Transition status report', (string) $input->getOption('transition-status'), 'Run transition-status to classify internal, owner, and host candidates.'),
            $this->artifact('transition_decision', 'Transition decision report', (string) $input->getOption('transition-decision'), 'Run transition-decision to decide whether internal waves can pause.'),
            $this->artifact('transition_pause_gate', 'Transition pause gate report', (string) $input->getOption('pause-gate'), 'Run transition-pause-gate as final advisory gate.'),
        ];

        $readyForOwnerSlices = 0 === count(array_filter($checklist, static fn (array $item): bool => 'missing' === $item['status']));
        $readyForHostSlice = (bool) $input->getOption('host-slice-ready');

        $recommendedNextActions = $readyForOwnerSlices
            ? [
                'Pause expansion of Administering-owned ecosystem tools.',
                'Request current slices for the owner repositories that should receive configuration tools first.',
                'Apply external neighbor kits only as reviewed overlay patches against each owner current slice.',
                'Keep host/post-application Symfony environment, credentials, and deployment configuration in a separate host configuration track.',
            ]
            : [
                'Generate the missing transition artifacts listed in the checklist.',
                'Re-run this checklist before switching to owner repository current-slice work.',
            ];

        if (!$readyForHostSlice) {
            $recommendedNextActions[] = 'Host/post-application current slice is not marked ready; handle host configuration tools in a separate later wave.';
        }

        $report = new AdministrationOwnerConfigurationToolTransitionHandoffChecklistReport(
            $checklist,
            $recommendedNextActions,
            $readyForOwnerSlices,
            $readyForHostSlice,
        );

        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetPath = $this->projectPath($writeJson);
            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create handoff checklist directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($targetPath, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side transition handoff checklist written to %s.', $targetPath));
        }

        $shouldFail = (bool) $input->getOption('fail-if-not-ready') && !$report->canStartOwnerSliceWork();
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $shouldFail ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner-side transition handoff checklist');
        $io->writeln(sprintf('Completed: <info>%d</info>', $report->completedCount()));
        $io->writeln(sprintf('Missing: <comment>%d</comment>', $report->missingCount()));
        $io->writeln(sprintf('Can start owner slice work: <info>%s</info>', $report->canStartOwnerSliceWork() ? 'yes' : 'not yet'));
        $io->writeln(sprintf('Next work mode: <info>%s</info>', $report->nextWorkMode()));

        $io->table(
            ['Key', 'Title', 'Status', 'Path', 'Note'],
            array_map(static fn (array $item): array => [
                $item['key'],
                $item['title'],
                $item['status'],
                $item['path'] ?? '-',
                $item['note'],
            ], $checklist),
        );

        $io->section('Recommended next actions');
        foreach ($report->recommendedNextActions as $action) {
            $io->writeln('- '.$action);
        }

        return $shouldFail ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array{key:string, title:string, status:string, path:?string, note:string} */
    private function artifact(string $key, string $title, string $path, string $missingNote): array
    {
        $absolutePath = $this->projectPath($path);

        return [
            'key' => $key,
            'title' => $title,
            'status' => is_file($absolutePath) ? 'ready' : 'missing',
            'path' => $path,
            'note' => is_file($absolutePath) ? 'Artifact is present.' : $missingNote,
        ];
    }

    /** @return array{key:string, title:string, status:string, path:?string, note:string} */
    private function directoryArtifact(string $key, string $title, string $path, string $missingNote): array
    {
        $absolutePath = $this->projectPath($path);
        $ready = is_dir($absolutePath)
            && is_file($absolutePath.'/README.adoc')
            && is_file($absolutePath.'/CHECKLIST.adoc')
            && is_file($absolutePath.'/handoff-report.json');

        return [
            'key' => $key,
            'title' => $title,
            'status' => $ready ? 'ready' : 'missing',
            'path' => $path,
            'note' => $ready ? 'Bundle directory has required files.' : $missingNote,
        ];
    }

    private function projectPath(string $path): string
    {
        if ('' === $path) {
            return $this->projectDir;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }

        return rtrim($this->projectDir, '/\\').'/'.ltrim($path, '/\\');
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
    }
}
