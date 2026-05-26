<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\PlannerInterface\Admin\AdministrationServiceToolRelocationPlannerInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationServiceToolRelocationPlanValidatorInterface;
use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanEntry;
use App\Administering\Value\Admin\AdministrationServiceToolRelocationPlanIssue;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:relocation-patch-plan',
    description: 'Builds a non-destructive touched-archive relocation patch plan for service-tool surface cleanup.',
)]
final class AdministrationServiceToolRelocationPatchPlanCommand extends Command
{
    public function __construct(
        private readonly AdministrationServiceToolRelocationPlannerInterface $planner,
        private readonly AdministrationServiceToolRelocationPlanValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key to plan, for example Managing or Rolling.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the patch plan as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write the patch plan JSON to a repository-relative path.')
            ->addOption('write-deleted-paths', null, InputOption::VALUE_REQUIRED, 'Write source paths that may be deleted manually after a reviewed relocation patch is applied.')
            ->addOption('allow-blocked', null, InputOption::VALUE_NONE, 'Return success even when some relocation entries are blocked by validation issues.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $sectionFilter = is_string($section) && '' !== trim($section) ? trim($section) : null;
        $entries = $this->planner->plan($sectionFilter);
        $issues = $this->validator->validate($entries);
        [$patchableEntries, $blockedEntries] = $this->splitEntries($entries, $issues);
        $deletedPaths = array_map(
            static fn (AdministrationServiceToolRelocationPlanEntry $entry): string => $entry->sourcePath,
            $patchableEntries,
        );

        $payload = [
            'sectionFilter' => $sectionFilter,
            'entryCount' => count($entries),
            'patchableEntryCount' => count($patchableEntries),
            'blockedEntryCount' => count($blockedEntries),
            'issueCount' => count($issues),
            'touchedArchiveOnly' => true,
            'destructiveActionsIncluded' => false,
            'automaticMoveAllowed' => false,
            'manualDeletionOnlyAfterReview' => true,
            'summary' => 'This command only prepares a relocation patch plan. It does not move, delete, overwrite, or rewrite files.',
            'patchableEntries' => array_map(static fn ($entry): array => $entry->toArray(), $patchableEntries),
            'blockedEntries' => array_map(static fn ($entry): array => $entry->toArray(), $blockedEntries),
            'manualDeleteAfterPatchPaths' => $deletedPaths,
            'issues' => array_map(static fn ($issue): array => $issue->toArray(), $issues),
        ];

        $writeJson = $input->getOption('write-json');
        if (is_string($writeJson) && '' !== trim($writeJson)) {
            $this->writeText(trim($writeJson), json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n");
        }

        $writeDeletedPaths = $input->getOption('write-deleted-paths');
        if (is_string($writeDeletedPaths) && '' !== trim($writeDeletedPaths)) {
            $this->writeText(trim($writeDeletedPaths), implode("\n", $deletedPaths).([] === $deletedPaths ? '' : "\n"));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return [] === $blockedEntries || (bool) $input->getOption('allow-blocked') ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        if ([] === $entries) {
            $io->success('No relocation candidates were found under src/Service.');

            return Command::SUCCESS;
        }

        $io->title('Service-tool relocation patch plan');
        $io->definitionList(
            ['Total candidates' => (string) count($entries)],
            ['Patchable candidates' => (string) count($patchableEntries)],
            ['Blocked candidates' => (string) count($blockedEntries)],
            ['Validation issues' => (string) count($issues)],
        );

        if ([] !== $patchableEntries) {
            $io->section('Patchable relocation candidates');
            $io->table(
                ['Section', 'Source', 'Target', 'Target namespace'],
                array_map(static fn (AdministrationServiceToolRelocationPlanEntry $entry): array => [
                    $entry->section,
                    $entry->sourcePath,
                    $entry->targetPath ?? '',
                    $entry->targetNamespace ?? '',
                ], $patchableEntries),
            );
        }

        if ([] !== $blockedEntries) {
            $io->section('Blocked relocation candidates');
            $io->table(
                ['Section', 'Source', 'Suggested target', 'Reason'],
                array_map(static fn (AdministrationServiceToolRelocationPlanEntry $entry): array => [
                    $entry->section,
                    $entry->sourcePath,
                    $entry->targetPath ?? '',
                    $entry->reason,
                ], $blockedEntries),
            );
        }

        if ([] !== $deletedPaths) {
            $io->section('Manual delete-after-patch paths');
            foreach ($deletedPaths as $deletedPath) {
                $io->writeln(' - '.$deletedPath);
            }
        }

        $io->writeln('No files were moved, deleted, overwritten, or rewritten by this command.');

        return [] === $blockedEntries || (bool) $input->getOption('allow-blocked') ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param list<AdministrationServiceToolRelocationPlanEntry> $entries
     * @param list<AdministrationServiceToolRelocationPlanIssue> $issues
     *
     * @return array{0: list<AdministrationServiceToolRelocationPlanEntry>, 1: list<AdministrationServiceToolRelocationPlanEntry>}
     */
    private function splitEntries(array $entries, array $issues): array
    {
        $blockedSources = [];
        foreach ($issues as $issue) {
            if ('error' === $issue->severity && null !== $issue->sourcePath) {
                $blockedSources[$issue->sourcePath] = true;
            }
        }

        $patchable = [];
        $blocked = [];
        foreach ($entries as $entry) {
            if (null === $entry->targetPath || null === $entry->targetNamespace || isset($blockedSources[$entry->sourcePath])) {
                $blocked[] = $entry;

                continue;
            }

            $patchable[] = $entry;
        }

        return [$patchable, $blocked];
    }

    private function writeText(string $repositoryRelativePath, string $contents): void
    {
        $path = dirname(__DIR__, 2).'/'.$repositoryRelativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents);
    }
}
