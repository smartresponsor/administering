<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ServiceInterface\Operation\AdministrationServiceSectionAnchorSyncOperationServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-section-anchors:sync',
    description: 'Synchronizes service-section primary CRUD anchor tables from their canonical providers.',
)]
/**
 * Synchronizes service-section primary CRUD anchor records from canonical providers and reports section-level synchronization outcomes.
 */
final class AdministrationServiceSectionAnchorSyncCommand extends Command
{
    public function __construct(private readonly AdministrationServiceSectionAnchorSyncOperationServiceInterface $syncOperationService)
    {
        parent::__construct();
    }

    /**
     * Declares the optional service-section selector and JSON output control for anchor synchronization.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section key to synchronize, for example Symfony or Connected.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the sync result as JSON.');
    }

    /**
     * Runs canonical anchor synchronization for the selected section set and returns failure when any section does not reach synced status.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $section = $input->getArgument('section');
        $results = $this->syncOperationService->synchronize(is_string($section) ? $section : null);

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode(array_map(static fn ($result): array => $result->toArray(), $results), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        foreach ($results as $result) {
            $io->writeln(sprintf('<info>%s</info>: %d records %s', $result->sectionKey, $result->recordCount, $result->status));
            foreach ($result->messages as $message) {
                $io->writeln(sprintf('  - %s', $message));
            }
        }

        return $this->hasFailure($results) ? Command::FAILURE : Command::SUCCESS;
    }

    /** @param list<\App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult> $results */
    private function hasFailure(array $results): bool
    {
        foreach ($results as $result) {
            if ('synced' !== $result->status) {
                return true;
            }
        }

        return false;
    }
}
