<?php

declare(strict_types=1);

namespace App\Administering\Command\Config;

use App\Administering\Service\Config\ConfigToolRegistryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:config:discover',
    description: 'Discovers connected applications and config tools, then refreshes the SQLite registry.',
)]
final class AdministrationConfigDiscoverCommand extends Command
{
    public function __construct(private readonly ConfigToolRegistryService $registryService)
    {
        parent::__construct();
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->registryService->sync();

        $io->success(sprintf('Discovered %d applications and %d config tools.', $result['applications'], $result['tools']));

        return Command::SUCCESS;
    }
}
