<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ValidatorInterface\Admin\ConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageReport;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolViolation;
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
    name: 'administering:owner-configuration-tools:external-package-spec',
    description: 'Builds a reviewed external handoff spec for owner-side configuration tool packages.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageSpecCommand extends Command
{
    /** @param iterable<ConfigurationToolProviderInterface> $ownerToolProviders */
    public function __construct(
        private readonly ConfigurationToolDefinitionValidatorInterface $validator,
        private readonly iterable $ownerToolProviders = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional owner component key/token, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print external package spec as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write external package spec to a JSON file path.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when no owner providers are discovered.')
            ->addOption('allow-rejected', null, InputOption::VALUE_NONE, 'Do not fail when some owner tools are rejected from the external package spec.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $providers = [];
        $entries = [];
        $rejectedEntries = [];

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

                $violations = $this->validator->validate($provider, $definition);
                $entry = $this->buildEntry($provider, $definition, $violations);

                if ($this->hasError($violations)) {
                    $rejectedEntries[] = $entry;
                    continue;
                }

                $entries[] = $entry;
            }
        }

        $report = new AdministrationOwnerConfigurationToolExternalPackageReport($providers, $entries, $rejectedEntries);
        $payload = $report->toArray();
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));

        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create external package spec directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writeJson, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side external package spec written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
        }

        $io->section('Owner-side external package spec');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Providers: <info>%d</info>', $report->providerCount()));
        $io->writeln(sprintf('Package entries: <info>%d</info>', $report->entryCount()));
        $io->writeln(sprintf('Rejected entries: <comment>%d</comment>', $report->rejectedCount()));

        if ([] === $providers) {
            $io->warning('No owner configuration tool providers were discovered. This is expected before neighboring owner repositories are wired into the container.');

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
        }

        if ([] !== $entries) {
            $io->section('External package entries');
            $io->table(
                ['Component', 'Tool key', 'Service path', 'Form path', 'Data path', 'Provider path'],
                array_map(static fn (array $entry): array => [
                    $entry['componentKey'],
                    $entry['toolKey'],
                    $entry['paths']['servicePath'],
                    $entry['paths']['formTypePath'] ?? '-',
                    $entry['paths']['formDataPath'] ?? '-',
                    $entry['paths']['providerPath'],
                ], $entries),
            );
        }

        if ([] !== $rejectedEntries) {
            $io->section('Rejected entries');
            $io->table(
                ['Component', 'Tool key', 'Service', 'Errors'],
                array_map(static fn (array $entry): array => [
                    $entry['componentKey'],
                    $entry['toolKey'],
                    $entry['serviceShortName'],
                    implode('
', array_map(
                        static fn (array $issue): string => sprintf('%s: %s', $issue['field'], $issue['message']),
                        array_filter($entry['validationIssues'], static fn (array $issue): bool => 'error' === $issue['severity']),
                    )),
                ], $rejectedEntries),
            );
        }

        return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
    }

    /**
     * @param list<AdministrationOwnerConfigurationToolViolation> $violations
     *
     * @return array<string, mixed>
     */
    private function buildEntry(
        ConfigurationToolProviderInterface $provider,
        ConfigurationToolDefinition $definition,
        array $violations,
    ): array {
        $componentKey = $this->normalizeComponentKey($definition->componentKey);
        $toolSlug = $definition->toolSlug;

        return [
            'componentKey' => $definition->componentKey,
            'componentToken' => $definition->componentToken,
            'toolKey' => $definition->toolKey(),
            'toolSlug' => $toolSlug,
            'label' => $definition->label,
            'providerClass' => $provider::class,
            'serviceClass' => $definition->serviceClass,
            'serviceShortName' => $definition->serviceShortName,
            'formTypeClass' => $definition->formTypeClass,
            'formDataClass' => $definition->formDataClass,
            'executable' => $definition->executable,
            'paths' => [
                'providerPath' => sprintf('%s/src/Provider/Configuration/%sConfigurationToolProvider.php', $componentKey, $componentKey),
                'servicePath' => sprintf('%s/src/Service/Configuration/%sConfiguration%sService.php', $componentKey, $componentKey, $toolSlug),
                'formTypePath' => null === $definition->formTypeClass ? null : sprintf('%s/src/Form/Configuration/%sConfiguration%sFormType.php', $componentKey, $componentKey, $toolSlug),
                'formDataPath' => null === $definition->formDataClass ? null : sprintf('%s/src/Value/Form/Configuration/%sConfiguration%sData.php', $componentKey, $componentKey, $toolSlug),
                'serviceConfigPath' => sprintf('%s/config/services/owner_configuration_tools.yaml', $componentKey),
            ],
            'copyMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'validationIssues' => array_map(
                static fn (AdministrationOwnerConfigurationToolViolation $violation): array => $violation->toArray(),
                $violations,
            ),
        ];
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

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageReport $report, bool $allowEmpty, bool $allowRejected): int
    {
        if (0 === $report->providerCount() && !$allowEmpty) {
            return Command::FAILURE;
        }

        if ($report->hasRejectedEntries() && !$allowRejected) {
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

    private function normalizeComponentKey(string $componentKey): string
    {
        $componentKey = trim($componentKey);

        return '' === $componentKey ? 'Unknown' : ucfirst($componentKey);
    }
}
