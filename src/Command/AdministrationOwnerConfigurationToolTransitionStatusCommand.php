<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\ServiceInterface\Tool\ConfigurationToolProviderInterface;
use App\Administering\Value\Admin\AdministrationOwnerConfigurationToolTransitionStatusReport;
use App\Administering\Value\Admin\AdministrationServiceTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:transition-status',
    description: 'Reports owner-side migration status for internal and owner-provided configuration tools.',
)]
final class AdministrationOwnerConfigurationToolTransitionStatusCommand extends Command
{
    /** @param iterable<ConfigurationToolProviderInterface> $ownerToolProviders */
    public function __construct(
        private readonly AdministrationServiceToolCatalogInterface $toolCatalog,
        private readonly iterable $ownerToolProviders = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('component', InputArgument::OPTIONAL, 'Optional component/section key or token, for example Managing or managing.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print transition status as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write transition status JSON to this path.')
            ->addOption('fail-on-owner-candidates', null, InputOption::VALUE_NONE, 'Fail when internal owner-extraction candidates remain.')
            ->addOption('fail-on-host-candidates', null, InputOption::VALUE_NONE, 'Fail when host-application extraction candidates remain.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $componentFilter = $this->normalizeOptionalString($input->getArgument('component'));

        $providers = $this->providerRows($componentFilter);
        $tools = [];
        $statusCounts = [];
        $issues = [];

        foreach ($this->toolCatalog->tools() as $tool) {
            if (!$this->matchesToolFilter($tool, $componentFilter)) {
                continue;
            }

            $status = $this->transitionStatus($tool);
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            $row = [
                'section' => $tool->section,
                'toolKey' => $tool->toolKey,
                'toolSlug' => $tool->toolSlug,
                'transitionStatus' => $status,
                'sourceOwnership' => $tool->sourceOwnership,
                'currentServiceShortName' => $tool->shortName,
                'currentServiceClass' => $tool->serviceClass,
                'ownerComponentKey' => $tool->ownerComponentKey,
                'ownerComponentToken' => $tool->ownerComponentToken,
                'ownerProviderClass' => $tool->ownerProviderClass,
                'ownerServiceClass' => $tool->ownerServiceClass,
                'recommendedOwnerServiceShortName' => $this->recommendedOwnerServiceShortName($tool),
                'recommendedOwnerServicePath' => $this->recommendedOwnerServicePath($tool),
                'recommendedOwnerFormTypePath' => $this->recommendedOwnerFormTypePath($tool),
                'recommendedOwnerFormDataPath' => $this->recommendedOwnerFormDataPath($tool),
                'formMapped' => $tool->hasForm(),
                'dataMapped' => $tool->hasFormDataClass(),
                'executable' => $tool->isExecutable(),
                'recommendedNextStep' => $this->recommendedNextStep($status),
            ];
            $tools[] = $row;

            if ('owner_extraction_candidate' === $status) {
                $issues[] = [
                    'severity' => 'warning',
                    'path' => $tool->toolKey,
                    'message' => 'Internal Administering tool should be moved to its owner component when the neighbor repository current slice is available.',
                ];
            }

            if ('host_application_candidate' === $status) {
                $issues[] = [
                    'severity' => 'warning',
                    'path' => $tool->toolKey,
                    'message' => 'Internal Administering tool appears to belong to the host/post-application configuration layer, not to an owner component.',
                ];
            }
        }

        usort($tools, static fn (array $left, array $right): int => [$left['transitionStatus'], $left['section'], $left['toolKey']] <=> [$right['transitionStatus'], $right['section'], $right['toolKey']]);
        ksort($statusCounts);

        $report = new AdministrationOwnerConfigurationToolTransitionStatusReport($componentFilter, $providers, $tools, $statusCounts, $issues);
        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetDirectory = dirname($writeJson);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create transition status report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($writeJson, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner-side transition status report written to %s.', $writeJson));
        }

        $shouldFail = $this->shouldFail($input, $report);
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $shouldFail ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner-side configuration tool transition status');
        $io->writeln(sprintf('Component filter: <info>%s</info>', $componentFilter ?? 'all'));
        $io->writeln(sprintf('Owner providers: <info>%d</info>', $report->providerCount()));
        $io->writeln(sprintf('Tools: <info>%d</info>', $report->toolCount()));
        $io->writeln(sprintf('Owner-ready: <info>%d</info>', $report->ownerReadyCount()));
        $io->writeln(sprintf('Owner extraction candidates: <comment>%d</comment>', $report->ownerExtractionCandidateCount()));
        $io->writeln(sprintf('Host application candidates: <comment>%d</comment>', $report->hostApplicationCandidateCount()));
        $io->writeln(sprintf('Admin shell-owned: <info>%d</info>', $report->adminShellOwnedCount()));

        if ([] !== $tools) {
            $io->table(
                ['Status', 'Section', 'Tool key', 'Current service', 'Recommended owner service', 'Form', 'Exec', 'Next step'],
                array_map(static fn (array $row): array => [
                    $row['transitionStatus'],
                    $row['section'],
                    $row['toolKey'],
                    $row['currentServiceShortName'],
                    $row['recommendedOwnerServiceShortName'] ?? '-',
                    true === $row['formMapped'] ? 'yes' : 'no',
                    true === $row['executable'] ? 'yes' : 'no',
                    $row['recommendedNextStep'],
                ], $tools),
            );
        }

        if (0 < $report->warningCount()) {
            $io->warning(sprintf('%d transition warning(s) found. Use --write-json to hand off a machine-readable extraction plan.', $report->warningCount()));
        }

        return $shouldFail ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return list<array{componentKey:string, componentToken:string, providerClass:string}> */
    private function providerRows(?string $componentFilter): array
    {
        $providers = [];
        foreach ($this->ownerToolProviders as $provider) {
            if (null !== $componentFilter
                && 0 !== strcasecmp($provider->componentKey(), $componentFilter)
                && 0 !== strcasecmp($provider->componentToken(), $componentFilter)
            ) {
                continue;
            }

            $providers[] = [
                'componentKey' => $provider->componentKey(),
                'componentToken' => $provider->componentToken(),
                'providerClass' => $provider::class,
            ];
        }

        usort($providers, static fn (array $left, array $right): int => [$left['componentToken'], $left['providerClass']] <=> [$right['componentToken'], $right['providerClass']]);

        return $providers;
    }

    private function transitionStatus(AdministrationServiceTool $tool): string
    {
        if ('owner_component' === $tool->sourceOwnership) {
            return 'owner_ready';
        }

        if (in_array($tool->section, ['Admin', 'Operation'], true)) {
            return 'admin_shell_owned';
        }

        if (in_array($tool->section, ['Configuration', 'Credential', 'Environment', 'Symfony'], true)) {
            return 'host_application_candidate';
        }

        return 'owner_extraction_candidate';
    }

    private function recommendedNextStep(string $status): string
    {
        return match ($status) {
            'owner_ready' => 'Keep owner provider wired and materialized into SQLite projection.',
            'admin_shell_owned' => 'Keep in Administering as orchestration/governance shell tool.',
            'host_application_candidate' => 'Move to host/post-application configuration layer, not to a component owner.',
            default => 'Prepare owner-side provider/form/service patch for the matching neighboring repository.',
        };
    }

    private function recommendedOwnerServiceShortName(AdministrationServiceTool $tool): ?string
    {
        if ('owner_component' === $tool->sourceOwnership || in_array($tool->section, ['Admin', 'Operation', 'Configuration', 'Credential', 'Environment', 'Symfony'], true)) {
            return null;
        }

        return $tool->section.'Configuration'.$tool->toolSlug.'Service';
    }

    private function recommendedOwnerServicePath(AdministrationServiceTool $tool): ?string
    {
        $shortName = $this->recommendedOwnerServiceShortName($tool);
        if (null === $shortName) {
            return null;
        }

        return $tool->section.'/src/Service/Configuration/'.$shortName.'.php';
    }

    private function recommendedOwnerFormTypePath(AdministrationServiceTool $tool): ?string
    {
        if (null === $this->recommendedOwnerServiceShortName($tool)) {
            return null;
        }

        return $tool->section.'/src/Form/Configuration/'.$tool->section.'Configuration'.$tool->toolSlug.'FormType.php';
    }

    private function recommendedOwnerFormDataPath(AdministrationServiceTool $tool): ?string
    {
        if (null === $this->recommendedOwnerServiceShortName($tool)) {
            return null;
        }

        return $tool->section.'/src/Value/Form/Configuration/'.$tool->section.'Configuration'.$tool->toolSlug.'Data.php';
    }

    private function matchesToolFilter(AdministrationServiceTool $tool, ?string $componentFilter): bool
    {
        if (null === $componentFilter) {
            return true;
        }

        return 0 === strcasecmp($tool->section, $componentFilter)
            || 0 === strcasecmp($tool->ownerComponentKey ?? '', $componentFilter)
            || 0 === strcasecmp($tool->ownerComponentToken ?? '', $componentFilter);
    }

    private function shouldFail(InputInterface $input, AdministrationOwnerConfigurationToolTransitionStatusReport $report): bool
    {
        if ((bool) $input->getOption('fail-on-owner-candidates') && 0 < $report->ownerExtractionCandidateCount()) {
            return true;
        }

        return (bool) $input->getOption('fail-on-host-candidates') && 0 < $report->hostApplicationCandidateCount();
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
