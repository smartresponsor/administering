<?php

declare(strict_types=1);

namespace App\Administering\Command\Config;

use App\Administering\Service\Config\ConfigFormResolverService;
use App\Administering\Service\Config\ConfigStateService;
use App\Administering\Service\Config\ConfigToolRegistryService;
use App\Administering\Service\Config\ConfigToolServiceLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:config:apply',
    description: 'Applies a trusted configuration tool using its registered Symfony Form and service class.',
)]
final class AdministrationConfigApplyCommand extends Command
{
    public function __construct(
        private readonly ConfigToolRegistryService $registryService,
        private readonly ConfigToolServiceLocator $toolServiceLocator,
        private readonly ConfigFormResolverService $formResolverService,
        private readonly ConfigStateService $stateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('application', InputArgument::REQUIRED, 'Application/component code.')
            ->addArgument('tool', InputArgument::REQUIRED, 'Configuration tool code.')
            ->addOption('save-only', null, InputOption::VALUE_NONE, 'Store pending state in SQLite without applying side effects.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $application = (string) $input->getArgument('application');
        $toolCode = (string) $input->getArgument('tool');
        $toolService = $this->toolServiceLocator->forTool($application, $toolCode);
        if (null === $toolService) {
            $io->error(sprintf('Unknown configuration tool "%s/%s".', $application, $toolCode));

            return Command::FAILURE;
        }

        $descriptor = null;
        foreach ($this->registryService->toolDescriptors() as $candidate) {
            if ($candidate->applicationCode === $application && $candidate->toolCode === $toolCode) {
                $descriptor = $candidate;
                break;
            }
        }

        if (null === $descriptor) {
            $io->error(sprintf('Descriptor for "%s/%s" is not registered.', $application, $toolCode));

            return Command::FAILURE;
        }

        if (null === $this->formResolverService->formClassForTool($application, $toolCode)) {
            $io->error(sprintf('No approved Symfony form is registered for "%s/%s".', $application, $toolCode));

            return Command::FAILURE;
        }

        $data = $this->stateService->hydratePendingValues($application, $toolCode, $toolService->loadData());
        $result = (bool) $input->getOption('save-only')
            ? $toolService->save($data, ['actor' => 'command'])
            : $toolService->apply($data, ['actor' => 'command']);

        $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return 'failed' === ($result['status'] ?? null) ? Command::FAILURE : Command::SUCCESS;
    }
}
