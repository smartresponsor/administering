<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeDecisionService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeOutputSchemaService;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeValidationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:report',
    description: 'Reports the normalized runtime-scope decision for every component token.',
)]
final class AdministrationRuntimeScopeReportCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopeDecisionService $decisionService,
        private readonly AdministrationRuntimeScopeValidationService $validationService,
        private readonly AdministrationRuntimeScopeOutputSchemaService $outputSchema,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', $this->projectDir)
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Runtime environment to inspect. Uses prod lock only for prod.', 'prod')
            ->addOption('max-age-seconds', null, InputOption::VALUE_REQUIRED, 'Fail when generatedAt is older than this many seconds. Use 0 to disable.', '0')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the report as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostDir = $this->absolutePath((string) $input->getOption('host-dir'));
        $environment = (string) $input->getOption('env');
        $maxAgeSeconds = max(0, (int) $input->getOption('max-age-seconds'));

        $decision = $this->decisionService->decide($hostDir, $environment);
        $validation = $this->validationService->validate($hostDir, $environment, $maxAgeSeconds);
        $report = $this->outputSchema->decisionPayload(
            'administration_runtime_scope_report',
            $decision,
            ['validation' => $validation->toArray()],
            array_values(array_unique([...$decision->sourceErrors(), ...$validation->errors])),
            $validation->warnings,
        );

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return [] === $report['errors'] ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Administration runtime-scope decision report');
        $io->writeln(sprintf('Host: <info>%s</info>', $decision->state->hostDir));
        $io->writeln(sprintf('Environment: <info>%s</info>', $decision->state->environment));
        $io->writeln(sprintf('APP_RUNTIME_SCOPE: <info>%s</info>', $decision->state->appRuntimeScopeRaw ?? ''));
        $io->writeln(sprintf('Composer: <info>%s</info>', $decision->state->composerPath));
        $io->writeln(sprintf('Runtime lock: <info>%s</info>', $decision->state->lockPath));

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
            ], $decision->componentRows()),
        );

        if ([] !== $report['warnings']) {
            $io->warning($report['warnings']);
        }

        if ([] !== $report['errors']) {
            $io->error($report['errors']);

            return Command::FAILURE;
        }

        $io->success('Runtime-scope decision report is clean.');

        return Command::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\/]/', $path)) {
            return rtrim($path, '/\\');
        }

        return rtrim($this->projectDir, '/\\').'/'.trim($path, '/\\');
    }
}
