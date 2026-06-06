<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\PlannerInterface\Admin\AdministrationServiceToolRelocationPlannerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:relocation-plan',
    description: 'Builds a non-destructive relocation plan for non-tool files under src/Service/<Direction>.',
)]
final class AdministrationServiceToolRelocationPlanCommand extends Command
{
    public function __construct(private readonly AdministrationServiceToolRelocationPlannerInterface $planner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key to plan, for example Connected or Symfony.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the relocation plan as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write the relocation plan JSON to a repository-relative path.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $sectionFilter = is_string($section) && '' !== trim($section) ? trim($section) : null;
        $entries = $this->planner->plan($sectionFilter);
        $payload = [
            'sectionFilter' => $sectionFilter,
            'entryCount' => count($entries),
            'destructiveActionsIncluded' => false,
            'entries' => array_map(static fn ($entry): array => $entry->toArray(), $entries),
        ];

        $writePath = $input->getOption('write-json');
        if (is_string($writePath) && '' !== trim($writePath)) {
            $this->writeJson(trim($writePath), $payload);
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return [] === $entries ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        if ([] === $entries) {
            $io->success('No relocation candidates were found under src/Service.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d non-tool src/Service file(s) have relocation recommendations.', count($entries)));
        $io->table(
            ['Section', 'Source', 'Action', 'Target', 'Target namespace', 'Review note'],
            array_map(static fn ($entry): array => [
                $entry->section,
                $entry->sourcePath,
                $entry->suggestedAction,
                $entry->targetPath ?? '',
                $entry->targetNamespace ?? '',
                $entry->reviewNote,
            ], $entries),
        );
        $io->writeln('This command is intentionally non-destructive: it does not move files, delete files, or rewrite namespaces.');

        return Command::FAILURE;
    }

    /** @param array<string, mixed> $payload */
    private function writeJson(string $repositoryRelativePath, array $payload): void
    {
        $path = dirname(__DIR__, 2).'/'.$repositoryRelativePath;
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n");
    }
}
