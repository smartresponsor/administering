<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeValidationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:validate',
    description: 'Hard-validates App Kernel runtime-scope lock files for CI/delivery gates.',
)]
final class AdministrationRuntimeScopeValidateCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopeValidationService $validationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', $this->projectDir)
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Runtime environment to validate. prod validates runtime_scope.prod.lock.php, any other env validates runtime_scope.lock.php.', 'prod')
            ->addOption('max-age-seconds', null, InputOption::VALUE_REQUIRED, 'Fail when generatedAt is older than this many seconds. Use 0 to disable.', '0')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print machine-readable validation output.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->validationService->validate(
            (string) $input->getOption('host-dir'),
            (string) $input->getOption('env'),
            max(0, (int) $input->getOption('max-age-seconds')),
        );

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $result->isValid() ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Administration runtime-scope validation');
        $io->writeln(sprintf('Host: <info>%s</info>', $result->hostDir));
        $io->writeln(sprintf('Environment: <info>%s</info>', $result->environment));
        $io->writeln(sprintf('Lock: <info>%s</info>', $result->lockFile));
        $io->writeln(sprintf('Composer: <info>%s</info>', $result->composerFile));

        if ([] !== $result->warnings) {
            $io->warning($result->warnings);
        }

        if (!$result->isValid()) {
            $io->error($result->errors);

            return Command::FAILURE;
        }

        $io->success('Runtime scope is valid.');

        return Command::SUCCESS;
    }
}
