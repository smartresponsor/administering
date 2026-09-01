<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\AuditorInterface\Admin\AdministrationServiceToolConventionAuditorInterface;
use App\Administering\Service\Admin\AdministrationAdminServiceToolRecordSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:refresh-index',
    description: 'Synchronizes the SQLite service-tool index and audits src/Service/<Direction> convention drift.',
)]
final class AdministrationServiceToolIndexRefreshCommand extends Command
{
    public function __construct(
        private readonly AdministrationAdminServiceToolRecordSyncService $syncService,
        private readonly AdministrationServiceToolConventionAuditorInterface $auditor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key to audit after sync, for example Connected or Symfony.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the refresh result as JSON.')
            ->addOption('allow-violations', null, InputOption::VALUE_NONE, 'Return success even when convention violations are found.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $sectionFilter = is_string($section) && '' !== trim($section) ? trim($section) : null;
        $syncResult = $this->syncService->synchronize();
        $violations = $this->auditor->violations($sectionFilter);
        $hasViolations = [] !== $violations;
        $allowViolations = (bool) $input->getOption('allow-violations');

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode([
                'sync' => $syncResult->toArray(),
                'audit' => [
                    'sectionFilter' => $sectionFilter,
                    'violationCount' => count($violations),
                    'violations' => array_map(static fn ($violation): array => $violation->toArray(), $violations),
                ],
                'status' => $hasViolations ? 'synced_with_convention_violations' : 'synced',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return $hasViolations && !$allowViolations ? Command::FAILURE : Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Service-tool index sync');
        $io->writeln(sprintf('<info>%s</info>: %d records %s', $syncResult->sectionKey, $syncResult->recordCount, $syncResult->status));
        foreach ($syncResult->messages as $message) {
            $io->writeln(sprintf('  - %s', $message));
        }

        $io->section('Service-tool convention audit');
        if (!$hasViolations) {
            $io->success('Service-tool index refreshed and no src/Service convention violations were found.');

            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d src/Service file(s) are not valid Administering service tools.', count($violations)));
        $io->table(
            ['Section', 'File', 'Reason', 'Suggestion', 'Suggested path'],
            array_map(static fn ($violation): array => [
                $violation->section,
                $violation->serviceFile,
                $violation->reason,
                $violation->suggestedAction,
                $violation->suggestedPath ?? '',
            ], $violations),
        );
        $io->writeln('The SQLite index was refreshed from valid tools only; invalid helper/provider/definition files were not materialized as tools.');

        return $allowViolations ? Command::SUCCESS : Command::FAILURE;
    }
}
