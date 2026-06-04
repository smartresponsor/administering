<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Scanner\RuntimeScope\AdministrationRuntimeScopeReferenceScanner;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeStateReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:runtime-scope:reference-audit',
    description: 'Audits host references against APP_ENV, APP_RUNTIME_SCOPE, composer inventory, and runtime-scope lock.',
)]
final class AdministrationRuntimeScopeReferenceAuditCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly AdministrationRuntimeScopeStateReader $stateReader,
        private readonly AdministrationRuntimeScopeReferenceScanner $referenceScanner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('host-dir', null, InputOption::VALUE_REQUIRED, 'Host application root directory.', $this->projectDir)
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Runtime environment to inspect. Uses prod lock only for prod.', 'prod')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print machine-readable JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hostDir = (string) $input->getOption('host-dir');
        $environment = (string) $input->getOption('env');
        $state = $this->stateReader->read($hostDir, $environment);

        $enabledComponents = [] !== $state->appRuntimeScope
            ? $state->appRuntimeScope
            : $state->enabledComponents;

        $forbiddenComponents = array_values(array_unique(array_merge(
            $state->disabledComponents,
            array_values(array_diff($state->installedComponents, $enabledComponents)),
        )));

        $findings = $this->referenceScanner->scan($state->hostDir, $forbiddenComponents);
        $errors = $state->sourceErrors;
        foreach ($findings as $finding) {
            $errors[] = sprintf(
                'Component "%s" is outside active runtime scope but is referenced in %s:%d through pattern "%s".',
                $finding['component'],
                $finding['file'],
                $finding['line'],
                $finding['pattern'],
            );
        }
        $errors = array_values(array_unique($errors));

        $report = [
            'report' => 'administration_runtime_scope_reference_audit',
            'hostDir' => $state->hostDir,
            'environment' => $state->environment,
            'composerFile' => $state->composerFile,
            'composerPath' => $state->composerPath,
            'lockPath' => $state->lockPath,
            'appRuntimeScope' => $state->appRuntimeScopeRaw,
            'enabledComponents' => $enabledComponents,
            'installedComponents' => $state->installedComponents,
            'disabledComponents' => $state->disabledComponents,
            'forbiddenComponents' => $forbiddenComponents,
            'findings' => $findings,
            'errors' => $errors,
        ];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return [] === $errors ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->section('Administration runtime-scope reference audit');
        $io->writeln(sprintf('Host: <info>%s</info>', $state->hostDir));
        $io->writeln(sprintf('Environment: <info>%s</info>', $state->environment));
        $io->writeln(sprintf('Composer: <info>%s</info>', $state->composerFile));
        $io->writeln(sprintf('Lock: <info>%s</info>', $state->lockPath));
        $io->writeln(sprintf('APP_RUNTIME_SCOPE: <info>%s</info>', $state->appRuntimeScopeRaw ?? '(not set)'));

        $io->table(
            ['Source', 'Components'],
            [
                ['enabled', implode(', ', $enabledComponents)],
                ['installed', implode(', ', $state->installedComponents)],
                ['disabled', implode(', ', $state->disabledComponents)],
                ['forbidden', implode(', ', $forbiddenComponents)],
            ],
        );

        if ([] !== $findings) {
            $io->table(
                ['File', 'Line', 'Component', 'Pattern', 'Excerpt'],
                array_map(static fn (array $finding): array => [
                    $finding['file'],
                    (string) $finding['line'],
                    $finding['component'],
                    $finding['pattern'],
                    $finding['excerpt'],
                ], $findings),
            );
        }

        if ([] !== $errors) {
            $io->error($errors);

            return Command::FAILURE;
        }

        $io->success('Runtime-scope references are clean.');

        return Command::SUCCESS;
    }
}
