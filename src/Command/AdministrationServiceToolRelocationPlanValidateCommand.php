<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\PlannerInterface\Admin\AdministrationServiceToolRelocationPlannerInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationServiceToolRelocationPlanValidatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:relocation-plan:validate',
    description: 'Validates the non-destructive relocation plan for conflicts before owner-reviewed patching.',
)]
final class AdministrationServiceToolRelocationPlanValidateCommand extends Command
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
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key to validate, for example Connected or Symfony.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the validation report as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write the validation report JSON to a repository-relative path.')
            ->addOption('allow-issues', null, InputOption::VALUE_NONE, 'Return success even when validation issues are found.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $sectionFilter = is_string($section) && '' !== trim($section) ? trim($section) : null;
        $entries = $this->planner->plan($sectionFilter);
        $issues = $this->validator->validate($entries);
        $payload = [
            'sectionFilter' => $sectionFilter,
            'entryCount' => count($entries),
            'issueCount' => count($issues),
            'destructiveActionsIncluded' => false,
            'automaticMoveAllowed' => false,
            'entries' => array_map(static fn ($entry): array => $entry->toArray(), $entries),
            'issues' => array_map(static fn ($issue): array => $issue->toArray(), $issues),
        ];

        $writePath = $input->getOption('write-json');
        if (is_string($writePath) && '' !== trim($writePath)) {
            $this->writeJson(trim($writePath), $payload);
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return [] === $issues || (bool) $input->getOption('allow-issues') ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        if ([] === $entries) {
            $io->success('No relocation candidates were found under src/Service.');

            return Command::SUCCESS;
        }

        if ([] === $issues) {
            $io->success(sprintf('Relocation plan contains %d candidate(s) and no path/namespace validation issues.', count($entries)));
            $io->writeln('This validation is non-destructive and does not approve automatic moves.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('Relocation plan contains %d candidate(s) and %d validation issue(s).', count($entries), count($issues)));
        $io->table(
            ['Severity', 'Code', 'Section', 'Source', 'Target', 'Message'],
            array_map(static fn ($issue): array => [
                $issue->severity,
                $issue->code,
                $issue->section ?? '',
                $issue->sourcePath ?? '',
                $issue->targetPath ?? '',
                $issue->message,
            ], $issues),
        );
        $io->writeln('No files were moved, deleted, overwritten, or rewritten.');

        return (bool) $input->getOption('allow-issues') ? Command::SUCCESS : Command::FAILURE;
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
