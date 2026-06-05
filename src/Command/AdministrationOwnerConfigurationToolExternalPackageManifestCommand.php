<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\ValidatorInterface\Admin\AdministrationConfigurationToolDefinitionValidatorInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolExternalPackageManifestReport;
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
    name: 'administering:owner-configuration-tools:external-package-manifest',
    description: 'Builds a grouped non-destructive owner-side external package manifest for neighboring repositories.',
)]
final class AdministrationOwnerConfigurationToolExternalPackageManifestCommand extends Command
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
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print external package manifest as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write external package manifest to a JSON file path.')
            ->addOption('allow-empty', null, InputOption::VALUE_NONE, 'Do not fail when no owner providers are discovered.')
            ->addOption('allow-rejected', null, InputOption::VALUE_NONE, 'Do not fail when some owner tools are rejected from the manifest.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));
        $providers = [];
        $componentManifests = [];
        $rejectedEntries = [];

        foreach ($this->ownerToolProviders as $provider) {
            if (!$this->matchesComponentFilter($provider, $componentFilter)) {
                continue;
            }

            $componentKey = $this->normalizeComponentKey($provider->componentKey());
            $componentToken = strtolower($provider->componentToken());
            $providers[] = [
                'componentKey' => $provider->componentKey(),
                'componentToken' => $provider->componentToken(),
                'providerClass' => $provider::class,
            ];

            $manifest = $componentManifests[$componentToken] ?? $this->emptyComponentManifest($componentKey, $componentToken, $provider::class);

            foreach ($provider->tools() as $definition) {
                if (!$definition instanceof ConfigurationToolDefinition) {
                    continue;
                }

                $violations = $this->validator->validate($provider, $definition);
                $toolEntry = $this->buildToolEntry($componentKey, $componentToken, $provider::class, $definition, $violations);

                if ($this->hasError($violations)) {
                    $rejectedEntries[] = $toolEntry;
                    continue;
                }

                $manifest['tools'][] = $toolEntry;
                $manifest['files'] = $this->mergeFiles($manifest['files'], $toolEntry['files']);
            }

            $componentManifests[$componentToken] = $manifest;
        }

        $report = new AdministrationOwnerConfigurationToolExternalPackageManifestReport($providers, array_values($componentManifests), $rejectedEntries);
        $payload = $report->toArray();
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));

        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create external package manifest directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writeJson, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side external package manifest written to %s.', $writeJson));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
        }

        $io->section('Owner-side external package manifest');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Providers: <info>%d</info>', $report->providerCount()));
        $io->writeln(sprintf('Components: <info>%d</info>', $report->componentCount()));
        $io->writeln(sprintf('Tools: <info>%d</info>', $report->entryCount()));
        $io->writeln(sprintf('Rejected entries: <comment>%d</comment>', $report->rejectedCount()));

        if (0 === $report->providerCount()) {
            $io->warning('No owner configuration tool providers were discovered. This is expected before neighboring owner repositories are wired into the container.');

            return $this->exitCode($report, (bool) $input->getOption('allow-empty'), (bool) $input->getOption('allow-rejected'));
        }

        foreach ($report->componentManifests as $manifest) {
            $io->section(sprintf('%s owner package', $manifest['componentKey']));
            $io->writeln(sprintf('Package root: <info>%s</info>', $manifest['packageRoot']));
            $io->writeln(sprintf('Provider: <info>%s</info>', $manifest['providerClass']));
            $io->writeln(sprintf('Files: <info>%d</info>', count($manifest['files'])));
            $io->table(
                ['Tool key', 'Service', 'Form', 'Data', 'Executable'],
                array_map(static fn (array $tool): array => [
                    $tool['toolKey'],
                    $tool['serviceShortName'],
                    $tool['formTypePath'] ?? '-',
                    $tool['formDataPath'] ?? '-',
                    $tool['executable'] ? 'yes' : 'no',
                ], $manifest['tools']),
            );
        }

        if ([] !== $rejectedEntries) {
            $io->section('Rejected owner entries');
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

    /** @return array<string, mixed> */
    private function emptyComponentManifest(string $componentKey, string $componentToken, string $providerClass): array
    {
        return [
            'manifestVersion' => 'owner-configuration-package.v1',
            'componentKey' => $componentKey,
            'componentToken' => $componentToken,
            'packageRoot' => $componentKey,
            'providerClass' => $providerClass,
            'deliveryMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'serviceConfigPath' => sprintf('%s/config/services/owner_configuration_tools.yaml', $componentKey),
            'providerPath' => sprintf('%s/src/Provider/Configuration/%sConfigurationToolProvider.php', $componentKey, $componentKey),
            'files' => [
                sprintf('%s/config/services/owner_configuration_tools.yaml', $componentKey),
                sprintf('%s/src/Provider/Configuration/%sConfigurationToolProvider.php', $componentKey, $componentKey),
            ],
            'tools' => [],
        ];
    }

    /**
     * @param list<AdministrationOwnerConfigurationToolViolation> $violations
     *
     * @return array<string, mixed>
     */
    private function buildToolEntry(string $componentKey, string $componentToken, string $providerClass, ConfigurationToolDefinition $definition, array $violations): array
    {
        $servicePath = sprintf('%s/src/Service/Configuration/%sConfiguration%sService.php', $componentKey, $componentKey, $definition->toolSlug);
        $formTypePath = null === $definition->formTypeClass ? null : sprintf('%s/src/Form/Configuration/%sConfiguration%sFormType.php', $componentKey, $componentKey, $definition->toolSlug);
        $formDataPath = null === $definition->formDataClass ? null : sprintf('%s/src/Value/Form/Configuration/%sConfiguration%sData.php', $componentKey, $componentKey, $definition->toolSlug);
        $files = [$servicePath];
        if (null !== $formTypePath) {
            $files[] = $formTypePath;
        }
        if (null !== $formDataPath) {
            $files[] = $formDataPath;
        }

        return [
            'componentKey' => $definition->componentKey,
            'componentToken' => $definition->componentToken,
            'toolKey' => $definition->toolKey(),
            'toolSlug' => $definition->toolSlug,
            'label' => $definition->label,
            'providerClass' => $providerClass,
            'serviceClass' => $definition->serviceClass,
            'serviceShortName' => $definition->serviceShortName,
            'formTypeClass' => $definition->formTypeClass,
            'formDataClass' => $definition->formDataClass,
            'executable' => $definition->executable,
            'servicePath' => $servicePath,
            'formTypePath' => $formTypePath,
            'formDataPath' => $formDataPath,
            'files' => $files,
            'copyMode' => 'overlay_only',
            'deleteMode' => 'none',
            'automaticMoveAllowed' => false,
            'validationIssues' => array_map(
                static fn (AdministrationOwnerConfigurationToolViolation $violation): array => $violation->toArray(),
                $violations,
            ),
        ];
    }

    /** @param list<string> $existing @param list<string> $incoming @return list<string> */
    private function mergeFiles(array $existing, array $incoming): array
    {
        $merged = $existing;
        foreach ($incoming as $file) {
            if (!in_array($file, $merged, true)) {
                $merged[] = $file;
            }
        }

        sort($merged);

        return $merged;
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

    private function exitCode(AdministrationOwnerConfigurationToolExternalPackageManifestReport $report, bool $allowEmpty, bool $allowRejected): int
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
