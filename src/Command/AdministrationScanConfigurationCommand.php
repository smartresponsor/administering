<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ServiceInterface\Configuration\AdministrationConfigurationScannerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'administering:configuration:scan', description: 'Scans host application configuration into a normalized safe view.')]
/**
 * Scans a host application configuration root into the normalized safe configuration view exposed by the Administering scanner contract.
 */
final class AdministrationScanConfigurationCommand extends Command
{
    public function __construct(private readonly AdministrationConfigurationScannerInterface $scanner)
    {
        parent::__construct();
    }

    /**
     * Declares the required host-root argument for normalized configuration scanning.
     */
    protected function configure(): void
    {
        $this->addArgument('host-root', InputArgument::REQUIRED, 'Host application root path.');
    }

    /**
     * Executes configuration scanning for the requested host root and reports normalized entry and warning counts.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostRoot = (string) $input->getArgument('host-root');
        $result = $this->scanner->scan($hostRoot);

        $output->writeln(sprintf('Scanned root: %s', $result->rootPath()));
        $output->writeln(sprintf('Entries: %d', count($result->entries())));
        foreach ($result->warnings() as $warning) {
            $output->writeln('<comment>'.$warning.'</comment>');
        }

        return Command::SUCCESS;
    }
}
