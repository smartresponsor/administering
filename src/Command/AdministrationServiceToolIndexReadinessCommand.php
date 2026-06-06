<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ProviderInterface\Admin\AdministrationServiceToolIndexReadinessProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:index-readiness',
    description: 'Reports EasyAdmin readiness of the materialized SQLite service-tool index.',
)]
final class AdministrationServiceToolIndexReadinessCommand extends Command
{
    public function __construct(private readonly AdministrationServiceToolIndexReadinessProviderInterface $readinessProvider)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key, for example Connected or Symfony.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the readiness report as JSON.')
            ->addOption('require-executable', null, InputOption::VALUE_NONE, 'Return failure unless all indexed tools are executable.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getArgument('section');
        $sectionFilter = is_string($section) && '' !== trim($section) ? trim($section) : null;
        $report = $this->readinessProvider->report($sectionFilter);
        $requireExecutable = (bool) $input->getOption('require-executable');

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return $requireExecutable && !$report->isFullyExecutable() ? Command::FAILURE : Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Service-tool index readiness');
        $io->writeln(sprintf('Section filter: <info>%s</info>', $sectionFilter ?? 'all'));
        $io->writeln(sprintf('Total records: <info>%d</info>', $report->totalCount));
        $io->writeln(sprintf('Executable: <info>%d</info>', $report->executableCount));
        $io->writeln(sprintf('Form-ready only: <comment>%d</comment>', $report->formReadyCount));
        $io->writeln(sprintf('Indexed only: <comment>%d</comment>', $report->indexedOnlyCount));

        if ([] === $report->records) {
            $io->warning('No service-tool records were found. Run administering:service-tools:refresh-index first.');

            return $requireExecutable ? Command::FAILURE : Command::SUCCESS;
        }

        $io->table(
            ['Section', 'Tool key', 'Display label', 'Source', 'Status', 'Executable', 'Form', 'Data'],
            array_map(static fn (array $record): array => [
                $record['sectionKey'],
                $record['toolKey'],
                $record['displayLabel'] ?? '',
                $record['sourceOwnership'] ?? '',
                $record['status'],
                true === $record['executable'] ? 'yes' : 'no',
                $record['formTypeClass'] ? 'yes' : 'no',
                $record['formDataClass'] ? 'yes' : 'no',
            ], $report->records),
        );

        if ($report->isFullyExecutable()) {
            $io->success('All indexed service tools are executable.');

            return Command::SUCCESS;
        }

        $io->note('Some indexed tools are not executable yet. This can be acceptable while tool forms/handlers are being introduced by wave.');

        return $requireExecutable ? Command::FAILURE : Command::SUCCESS;
    }
}
