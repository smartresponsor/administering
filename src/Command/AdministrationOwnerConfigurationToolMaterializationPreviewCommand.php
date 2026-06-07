<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolMaterializationReport;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:materialization-preview',
    description: 'Shows which owner-provided configuration tools are eligible for SQLite/EasyAdmin materialization.',
)]
final class AdministrationOwnerConfigurationToolMaterializationPreviewCommand extends Command
{
    /** @param iterable<ConfigurationToolProviderInterface> $ownerToolProviders */
    public function __construct(
        private readonly AdministrationConfigurationToolDefinitionValidatorInterface $validator,
        private readonly iterable $ownerToolProviders = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional owner component key/token, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print materialization preview as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write materialization preview to a JSON file path.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when no owner providers are discovered.')
            ->addOption('allow-rejected', null, InputOption::VALUE_NONE, 'Do not fail when some owner tools are rejected from materialization.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $providers = [];
        $acceptedTools = [];
        $rejectedTools = [];

        foreach ($this->ownerToolProviders as $provider) {
            if (!$this->matchesComponentFilter($provider, $componentFilter)) {
                continue;
            }

            $providers[] = [
                'componentKey' => $provider->componentKey(),
                'componentToken' => $provider->componentToken(),
                'providerClass' => $provider::class,
            ];

            foreach ($provider->tools() as $definition) {
                $violations = $this->validator->validate($provider, $definition);
                $row = $definition->toArray();
                $row['providerClass'] = $provider::class;
                $row['validationIssues'] = array_map(
                    static fn (AdministrationOwnerConfigurationToolViolation $violation): array => $violation->toArray(),
                    $violations,
                );

                if ($this->hasError($violations)) {
                    $row['materializationStatus'] = 'rejected';
                    $rejectedTools[] = $row;
                    continue;
                }

                $row['materializationStatus'] = [] === $violations ? 'accepted' : 'accepted_with_warnings';
                $acceptedTools[] = $row;
            }
        }

        $report = new AdministrationOwnerConfigurationToolMaterializationReport($providers, $acceptedTools, $rejectedTools);
        $payload = $report->toArray();
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));

        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create materialization preview directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writeJson, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner configuration tool materialization preview written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
        }

        $io->section('Owner configuration tool materialization preview');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Providers: <info>%d</info>', $report->providerCount()));
        $io->writeln(sprintf('Accepted tools: <info>%d</info>', $report->acceptedCount()));
        $io->writeln(sprintf('Rejected tools: <comment>%d</comment>', $report->rejectedCount()));

        if ([] === $providers) {
            $io->warning('No owner configuration tool providers were discovered. This is expected before neighboring owner repositories are wired into the host/Administering container.');

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
        }

        if ([] !== $acceptedTools) {
            $io->section('Accepted for materialization');
            $io->table(
                ['Component', 'Tool key', 'Service', 'Form', 'Data', 'Executable', 'Status'],
                array_map(static fn (array $row): array => [
                    $row['componentKey'],
                    $row['toolKey'],
                    $row['serviceShortName'],
                    $row['formTypeClass'] ? 'yes' : 'no',
                    $row['formDataClass'] ? 'yes' : 'no',
                    true === $row['executable'] ? 'yes' : 'no',
                    $row['materializationStatus'],
                ], $acceptedTools),
            );
        }

        if ([] !== $rejectedTools) {
            $io->section('Rejected from materialization');
            $io->table(
                ['Component', 'Tool key', 'Service', 'Errors'],
                array_map(static fn (array $row): array => [
                    $row['componentKey'],
                    $row['toolKey'],
                    $row['serviceShortName'],
                    implode("\n", array_map(
                        static fn (array $issue): string => sprintf('%s: %s', $issue['field'], $issue['message']),
                        array_filter($row['validationIssues'], static fn (array $issue): bool => 'error' === $issue['severity']),
                    )),
                ], $rejectedTools),
            );
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
    }

    /** @param list<AdministrationOwnerConfigurationToolViolation> $violations */
    private function hasError(array $violations): bool
    {
        foreach ($violations as $violation) {
            if ($violation->isError()) {
                return true;
            }
        }

        return false;
    }

    private function exitCode(AdministrationOwnerConfigurationToolMaterializationReport $report, bool $allowEmpty, bool $allowRejected): int
    {
        if (0 === $report->providerCount() && !$allowEmpty) {
            return Command::FAILURE;
        }

        if ($report->hasRejectedTools() && !$allowRejected) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function matchesComponentFilter(ConfigurationToolProviderInterface $provider, ?string $componentFilter): bool
    {
        if (null === $componentFilter) {
            return true;
        }

        return 0 === strcasecmp($provider->componentKey(), $componentFilter)
            || 0 === strcasecmp($provider->componentToken(), $componentFilter);
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
