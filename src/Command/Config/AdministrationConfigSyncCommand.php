<?php

declare(strict_types=1);

namespace App\Administering\Command\Config;

use App\Administering\Service\Config\AdministrationConfigToolRegistryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:config:sync',
    description: 'Synchronizes the SQLite configuration registry from trusted component manifests.',
)]
final class AdministrationConfigSyncCommand extends Command
{
    public function __construct(private readonly AdministrationConfigToolRegistryService $registryService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->registryService->sync();

        $io->writeln(sprintf('Registry sync complete: <info>%d</info> applications, <info>%d</info> tools.', $result['applications'], $result['tools']));

        return Command::SUCCESS;
    }
}
