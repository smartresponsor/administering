<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Configuring\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Configuring\Value\Tool\ConfigurationToolDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:discover',
    description: 'Reports owner-provided configuration tools before they are materialized into the Administering SQLite/EasyAdmin projection.',
)]
final class AdministrationOwnerConfigurationToolDiscoveryCommand extends Command
{
    /** @param iterable<ConfigurationToolProviderInterface> $ownerToolProviders */
    public function __construct(private readonly iterable $ownerToolProviders = [])
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional owner component key/token, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the discovery report as JSON.')
            ->addOption('require-owner-prefix', null, InputOption::VALUE_NONE, 'Fail when any owner tool service does not use the owner-side Configuration prefix.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write the discovery report to a JSON file path.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $requireOwnerPrefix = (bool) $input->getOption('require-owner-prefix');
        $providers = [];
        $tools = [];
        $prefixViolations = [];

        foreach ($this->ownerToolProviders as $provider) {
            if (!$this->matchesComponentFilter($provider, $componentFilter)) {
                continue;
            }

            $providerRow = [
                'componentKey' => $provider->componentKey(),
                'componentToken' => $provider->componentToken(),
                'providerClass' => $provider::class,
            ];
            $providers[] = $providerRow;

            foreach ($provider->tools() as $definition) {
                if (!$definition instanceof ConfigurationToolDefinition) {
                    continue;
                }

                $row = $definition->toArray() + [
                    'providerComponentKey' => $provider->componentKey(),
                    'providerComponentToken' => $provider->componentToken(),
                    'providerClass' => $provider::class,
                ];
                $tools[] = $row;

                if (true !== $row['ownerSidePrefixed']) {
                    $prefixViolations[] = [
                        'toolKey' => $row['toolKey'],
                        'serviceShortName' => $row['serviceShortName'],
                        'expectedServicePrefix' => $row['expectedServicePrefix'],
                    ];
                }
            }
        }

        usort($tools, static fn (array $left, array $right): int => [$left['componentToken'], $left['toolKey']] <=> [$right['componentToken'], $right['toolKey']]);

        $payload = [
            'schema' => 'administering.owner_configuration_tool_discovery.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'componentFilter' => $componentFilter,
            'providerCount' => count($providers),
            'toolCount' => count($tools),
            'prefixViolationCount' => count($prefixViolations),
            'providers' => $providers,
            'tools' => $tools,
            'prefixViolations' => $prefixViolations,
        ];

        $writeJson = $input->getOption('write-json');
        if (null !== $writeJson) {
            if (!is_string($writeJson) || '' === trim($writeJson)) {
                $io->error('The --write-json path must not be blank.');

                return Command::INVALID;
            }

            $targetPath = trim($writeJson);
            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create discovery report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($targetPath, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner configuration tool discovery report written to %s.', $targetPath));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $requireOwnerPrefix && [] !== $prefixViolations ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner configuration tool discovery');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Providers: <info>%d</info>', count($providers)));
        $io->writeln(sprintf('Tools: <info>%d</info>', count($tools)));
        $io->writeln(sprintf('Owner-prefix violations: <comment>%d</comment>', count($prefixViolations)));

        if ([] === $providers) {
            $io->warning('No owner configuration tool providers were discovered. This is expected before neighboring components are wired into the host/Administering container.');

            return $requireOwnerPrefix ? Command::FAILURE : Command::SUCCESS;
        }

        $io->table(
            ['Component', 'Tool key', 'Service', 'Owner prefix', 'Form', 'Data', 'Executable'],
            array_map(static fn (array $row): array => [
                $row['componentKey'],
                $row['toolKey'],
                $row['serviceShortName'],
                true === $row['ownerSidePrefixed'] ? 'yes' : 'no',
                $row['formTypeClass'] ? 'yes' : 'no',
                $row['formDataClass'] ? 'yes' : 'no',
                true === $row['executable'] ? 'yes' : 'no',
            ], $tools),
        );

        if ([] !== $prefixViolations) {
            $io->warning('Some owner tools do not use the owner-side Configuration prefix. Keep Administering-prefixed services only inside Administering.');
        }

        return $requireOwnerPrefix && [] !== $prefixViolations ? Command::FAILURE : Command::SUCCESS;
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
