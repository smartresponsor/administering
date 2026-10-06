<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ServiceInterface\Tool\AdministrationConfigurationToolProviderInterface;
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
/**
 * Audits owner-provided configuration tool metadata before Administering materializes its operator projection.
 *
 * The command keeps discovery read-only, can enforce owner-side service naming, and exposes the same
 * deterministic inventory as either human output or a JSON handoff artifact for repository governance.
 */
final class AdministrationOwnerConfigurationToolDiscoveryCommand extends Command
{
    /** @param iterable<AdministrationConfigurationToolProviderInterface> $ownerToolProviders */
    public function __construct(private readonly iterable $ownerToolProviders = [])
    {
        parent::__construct();
    }

    /**
     * Declares filtering, machine-output, naming-enforcement, and artifact options for discovery runs.
     */
    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional owner component key/token, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the discovery report as JSON.')
            ->addOption('require-owner-prefix', null, InputOption::VALUE_NONE, 'Fail when any owner tool service does not use the owner-side Configuration prefix.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write the discovery report to a JSON file path.');
    }

    /**
     * Discovers owner tool providers, publishes the requested report, and fails when enforced naming invariants are violated.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $requireOwnerPrefix = (bool) $input->getOption('require-owner-prefix');
        $discovery = $this->discover($componentFilter);
        $payload = $this->buildPayload($componentFilter, $discovery['providers'], $discovery['tools'], $discovery['prefixViolations']);

        $writeResult = $this->writeJsonReport($input->getOption('write-json'), $payload, $io);
        if (null !== $writeResult) {
            return $writeResult;
        }

        $exitCode = $this->exitCode($requireOwnerPrefix, $discovery['prefixViolations']);
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $exitCode;
        }

        $this->renderHumanReport($io, $componentFilter, $discovery['providers'], $discovery['tools'], $discovery['prefixViolations']);

        return $exitCode;
    }

    /**
     * @return array{
     *   providers: list<array{componentKey: string, componentToken: string, providerClass: class-string}>,
     *   tools: list<array<string, mixed>>,
     *   prefixViolations: list<array{toolKey: mixed, serviceShortName: mixed, expectedServicePrefix: mixed}>
     * }
     */
    private function discover(?string $componentFilter): array
    {
        $providers = [];
        $tools = [];
        $prefixViolations = [];

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

        return [
            'providers' => $providers,
            'tools' => $tools,
            'prefixViolations' => $prefixViolations,
        ];
    }

    /**
     * @param list<array{componentKey: string, componentToken: string, providerClass: class-string}> $providers
     * @param list<array<string, mixed>>                                                             $tools
     * @param list<array{toolKey: mixed, serviceShortName: mixed, expectedServicePrefix: mixed}>     $prefixViolations
     *
     * @return array<string, mixed>
     */
    private function buildPayload(?string $componentFilter, array $providers, array $tools, array $prefixViolations): array
    {
        return [
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
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJsonReport(mixed $writeJson, array $payload, SymfonyStyle $io): ?int
    {
        if (null === $writeJson) {
            return null;
        }

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

        return null;
    }

    /**
     * @param list<array{componentKey: string, componentToken: string, providerClass: class-string}> $providers
     * @param list<array<string, mixed>>                                                             $tools
     * @param list<array{toolKey: mixed, serviceShortName: mixed, expectedServicePrefix: mixed}>     $prefixViolations
     */
    private function renderHumanReport(
        SymfonyStyle $io,
        ?string $componentFilter,
        array $providers,
        array $tools,
        array $prefixViolations,
    ): void {
        $io->section('Owner configuration tool discovery');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Providers: <info>%d</info>', count($providers)));
        $io->writeln(sprintf('Tools: <info>%d</info>', count($tools)));
        $io->writeln(sprintf('Owner-prefix violations: <comment>%d</comment>', count($prefixViolations)));

        if ([] === $providers) {
            $io->warning('No owner configuration tool providers were discovered. This is expected before neighboring components are wired into the host/Administering container.');

            return;
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
    }

    /**
     * @param list<array{toolKey: mixed, serviceShortName: mixed, expectedServicePrefix: mixed}> $prefixViolations
     */
    private function exitCode(bool $requireOwnerPrefix, array $prefixViolations): int
    {
        return $requireOwnerPrefix && [] !== $prefixViolations ? Command::FAILURE : Command::SUCCESS;
    }

    private function matchesComponentFilter(AdministrationConfigurationToolProviderInterface $provider, ?string $componentFilter): bool
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
