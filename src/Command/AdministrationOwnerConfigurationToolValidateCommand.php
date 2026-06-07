<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Administering\ValidatorInterface\Admin\AdministrationConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:validate',
    description: 'Validates owner-provided configuration tool definitions before SQLite/EasyAdmin materialization.',
)]
final class AdministrationOwnerConfigurationToolValidateCommand extends Command
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
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print validation report as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write validation report to a JSON file path.')
            ->addOption('allow-warnings', null, InputOption::VALUE_NONE, 'Do not fail on warning-level validation issues.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when no owner providers are discovered.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $providers = [];
        $tools = [];
        $violations = [];

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
                $tools[] = $definition->toArray() + ['providerClass' => $provider::class];

                foreach ($this->validator->validate($provider, $definition) as $violation) {
                    $violations[] = $violation;
                }
            }
        }

        $errorCount = count(array_filter($violations, static fn (AdministrationOwnerConfigurationToolViolation $violation): bool => $violation->isError()));
        $warningCount = count($violations) - $errorCount;

        $payload = [
            'schema' => 'administering.owner_configuration_tool_validation.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'componentFilter' => $componentFilter,
            'providerCount' => count($providers),
            'toolCount' => count($tools),
            'errorCount' => $errorCount,
            'warningCount' => $warningCount,
            'providers' => $providers,
            'tools' => $tools,
            'violations' => array_map(static fn (AdministrationOwnerConfigurationToolViolation $violation): array => $violation->toArray(), $violations),
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
                $io->error(sprintf('Unable to create validation report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($targetPath, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner configuration tool validation report written to %s.', $targetPath));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($providers, $errorCount, $warningCount, (bool) $input->getOption('allow-warnings'), (bool) $input->getOption('allow-empty'));
        }

        $io->section('Owner configuration tool validation');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Providers: <info>%d</info>', count($providers)));
        $io->writeln(sprintf('Tools: <info>%d</info>', count($tools)));
        $io->writeln(sprintf('Errors: <error>%d</error>', $errorCount));
        $io->writeln(sprintf('Warnings: <comment>%d</comment>', $warningCount));

        if ([] === $providers) {
            $io->warning('No owner configuration tool providers were discovered. This is expected before neighboring components are wired into the host/Administering container.');
        }

        if ([] !== $violations) {
            $io->table(
                ['Severity', 'Component', 'Tool key', 'Field', 'Message', 'Expected', 'Actual'],
                array_map(static fn (AdministrationOwnerConfigurationToolViolation $violation): array => [
                    $violation->severity,
                    $violation->componentKey,
                    $violation->toolKey,
                    $violation->field,
                    $violation->message,
                    $violation->expected ?? '',
                    $violation->actual ?? '',
                ], $violations),
            );
        }

        return $this->exitCode($providers, $errorCount, $warningCount, (bool) $input->getOption('allow-warnings'), (bool) $input->getOption('allow-empty'));
    }

    /** @param list<array{componentKey:string, componentToken:string, providerClass:class-string}> $providers */
    private function exitCode(array $providers, int $errorCount, int $warningCount, bool $allowWarnings, bool $allowEmpty): int
    {
        if ([] === $providers && !$allowEmpty) {
            return Command::FAILURE;
        }

        if (0 < $errorCount) {
            return Command::FAILURE;
        }

        if (0 < $warningCount && !$allowWarnings) {
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
