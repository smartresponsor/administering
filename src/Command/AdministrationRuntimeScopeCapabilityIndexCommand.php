<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeDecisionService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeOutputSchemaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:capability-index',
    description: 'Builds a runtime capability index from APP_ENV, APP_RUNTIME_SCOPE, composer inventory, and runtime-scope lock facts.',
)]
final class AdministrationRuntimeScopeCapabilityIndexCommand extends Command
{
    public function __construct(
        private readonly AdministrationRuntimeScopeDecisionService $decisionService,
        private readonly AdministrationRuntimeScopeOutputSchemaService $outputSchema,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', getcwd() ?: '.')
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Runtime environment to inspect.', $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostDir = (string) $input->getOption('host-dir');
        $environment = (string) $input->getOption('env');
        $decision = $this->decisionService->decide($hostDir, $environment);
        $payload = $this->outputSchema->decisionPayload('administration_runtime_scope_capability_index', $decision);
        $source = $payload['source'];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Administration runtime-scope capability index');
        $io->writeln(sprintf('Host: <info>%s</info>', $source['hostDir']));
        $io->writeln(sprintf('APP_ENV: <info>%s</info>', $source['environment']));
        $io->writeln(sprintf('APP_RUNTIME_SCOPE: <info>%s</info>', '' !== $source['appRuntimeScope'] ? $source['appRuntimeScope'] : 'administering (default)'));
        $io->table(
            ['Component', 'Present', 'Allowed', 'Locked', 'Enabled', 'Status', 'Reason'],
            array_map(static fn (array $row): array => [
                $row['component'],
                true === $row['present'] ? 'yes' : 'no',
                true === $row['allowed'] ? 'yes' : 'no',
                true === $row['locked'] ? 'yes' : 'no',
                true === $row['enabled'] ? 'yes' : 'no',
                $row['status'],
                $row['reason'],
            ], $payload['components']),
        );

        if ([] !== $payload['errors']) {
            $io->warning($payload['errors']);
        }

        return Command::SUCCESS;
    }
}
