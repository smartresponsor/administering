<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerRepositoryWorkOrderReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:owner-work-order',
    description: 'Builds the final work order for requesting owner/host current slices and starting concrete repository-specific patch waves.',
)]
final class AdministrationOwnerRepositoryWorkOrderCommand extends Command
{
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('components', InputArgument::IS_ARRAY, 'Owner component keys to request as current slices, for example connected symfony environment.')
            ->addOption('host-application', null, InputOption::VALUE_REQUIRED, 'Host/post-application repository key to include as its own configuration track.')
            ->addOption('workspace-root', null, InputOption::VALUE_REQUIRED, 'Expected local workspace root for owner repositories.', 'D:\\PhpstormProjects\\www')
            ->addOption('patch-readiness-json', null, InputOption::VALUE_REQUIRED, 'Owner repository patch readiness JSON path.', 'delivery/patches/administering_owner_repository_patch_readiness.json')
            ->addOption('transition-freeze-json', null, InputOption::VALUE_REQUIRED, 'Transition freeze JSON path.', 'delivery/patches/administering_owner_configuration_tool_transition_freeze.json')
            ->addOption('external-kit-name', null, InputOption::VALUE_REQUIRED, 'Expected external migration kit/archive label.', 'administering_owner_side_transition_freeze_external_neighbors.zip')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print work order as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write work order JSON to this path.')
            ->addOption('allow-missing-artifacts', null, InputOption::VALUE_NONE, 'Allow missing readiness/freeze artifacts for advisory work orders.')
            ->addOption('fail-if-no-components', null, InputOption::VALUE_NONE, 'Fail when no owner components are provided.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $components = $this->normalizeComponents($input->getArgument('components'));
        $hostApplication = $this->normalizeOptionalString($input->getOption('host-application'));
        $workspaceRoot = (string) $input->getOption('workspace-root');
        $patchReadinessPath = $this->projectPath((string) $input->getOption('patch-readiness-json'));
        $transitionFreezePath = $this->projectPath((string) $input->getOption('transition-freeze-json'));
        $externalKitName = (string) $input->getOption('external-kit-name');

        if ([] === $components && (bool) $input->getOption('fail-if-no-components')) {
            $io->error('No owner components were provided for work order generation.');

            return Command::FAILURE;
        }

        $artifactReferences = [
            [
                'name' => 'owner repository patch readiness report',
                'path' => $patchReadinessPath,
                'status' => is_file($patchReadinessPath) ? 'present' : 'missing',
            ],
            [
                'name' => 'transition freeze report',
                'path' => $transitionFreezePath,
                'status' => is_file($transitionFreezePath) ? 'present' : 'missing',
            ],
            [
                'name' => 'external neighbors migration kit',
                'path' => $externalKitName,
                'status' => 'expected',
            ],
        ];

        $missingArtifactCount = count(array_filter($artifactReferences, static fn (array $item): bool => 'missing' === $item['status']));
        if ($missingArtifactCount > 0 && !(bool) $input->getOption('allow-missing-artifacts')) {
            $io->error('Required transition artifacts are missing. Re-run with --allow-missing-artifacts only for advisory work orders.');

            return Command::FAILURE;
        }

        $patchReadiness = $this->loadJsonFile($patchReadinessPath);
        $readinessByComponent = $this->readinessByComponent($patchReadiness);
        $repositoryWorkOrders = [];
        foreach ($components as $componentKey) {
            $componentToken = $this->componentToken($componentKey);
            $expectedRepositoryPath = rtrim($workspaceRoot, '/\\').'\\'.$componentKey;
            $readiness = $readinessByComponent[$componentKey] ?? $readinessByComponent[$componentToken] ?? null;
            $sliceStatus = is_array($readiness) ? (string) ($readiness['sliceStatus']) : 'not_reported';

            $repositoryWorkOrders[] = [
                'componentKey' => $componentKey,
                'componentToken' => $componentToken,
                'expectedRepositoryPath' => $expectedRepositoryPath,
                'currentSliceRequired' => true,
                'expectedCurrentSliceArchive' => $componentKey.'.zip',
                'ownerSidePrefixConvention' => $componentKey.'Configuration{ToolSlug}Service',
                'ownerProviderTarget' => $componentKey.'/src/Provider/Configuration/'.$componentKey.'ConfigurationToolProvider.php',
                'ownerServiceTargetPattern' => $componentKey.'/src/Service/Configuration/'.$componentKey.'Configuration{ToolSlug}Service.php',
                'ownerFormTargetPattern' => $componentKey.'/src/Form/Configuration/'.$componentKey.'Configuration{ToolSlug}FormType.php',
                'ownerFormDataTargetPattern' => $componentKey.'/src/Value/Form/Configuration/'.$componentKey.'Configuration{ToolSlug}Data.php',
                'expectedDeliverables' => [
                    $componentToken.'_owner_configuration_touched.zip',
                    $componentToken.'_owner_configuration_cumulative.zip',
                    $componentToken.'_owner_configuration_apply_touched.ps1',
                ],
                'readinessFromReport' => $sliceStatus,
                'nextAction' => 'Provide the repository current slice before generating a concrete touched patch; do not apply the generic external kit as a blind patch.',
            ];
        }

        $hostApplicationWorkOrders = [];
        if (null !== $hostApplication) {
            $hostApplicationWorkOrders[] = [
                'repositoryKey' => $hostApplication,
                'track' => 'host_application_configuration',
                'currentSliceRequired' => true,
                'expectedCurrentSliceArchive' => $hostApplication.'.zip',
                'ownedConcerns' => [
                    'Symfony framework configuration',
                    'environment configuration',
                    'credentials and secrets integration',
                    'component enablement',
                    'system SQLite entity manager wiring',
                ],
                'expectedDeliverables' => [
                    $this->componentToken($hostApplication).'_host_configuration_touched.zip',
                    $this->componentToken($hostApplication).'_host_configuration_cumulative.zip',
                    $this->componentToken($hostApplication).'_host_configuration_apply_touched.ps1',
                ],
                'nextAction' => 'Provide the host/post-application current slice as a separate track; do not mix host environment/credential changes into owner component repositories.',
            ];
        }

        $administeringShellWorkOrders = [
            [
                'repositoryKey' => 'Administering',
                'track' => 'thin_orchestration_shell',
                'ownedConcerns' => [
                    'owner tool discovery',
                    'owner tool validation',
                    'SQLite materialized projection',
                    'EasyAdmin CRUD index',
                    'runtime controls',
                    'audit/governance shell',
                    'external handoff reports',
                ],
                'forbiddenExpansion' => 'Do not add new ecosystem-owned configuration tools directly into Administering after transition freeze.',
                'allowedWork' => 'Shell bugfixes, validation/reporting hardening, owner discovery/readiness improvements.',
            ],
        ];

        $recommendedNextActions = [
            'Stop expanding ecosystem-owned configuration tools inside Administering.',
            'Collect current slices for owner repositories listed in repositoryWorkOrders.',
            'Collect the host/post-application current slice when environment, credentials, Symfony config, or component enablement are in scope.',
            'Build repository-specific touched patches from each current slice rather than applying generic external kit blindly.',
        ];

        $nextWorkMode = [] === $repositoryWorkOrders
            ? 'provide_owner_repository_list'
            : 'request_owner_repository_current_slices_first';

        $report = new AdministrationOwnerRepositoryWorkOrderReport(
            $repositoryWorkOrders,
            $hostApplicationWorkOrders,
            $administeringShellWorkOrders,
            $artifactReferences,
            $recommendedNextActions,
            $nextWorkMode,
        );

        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetPath = $this->projectPath($writeJson);
            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create owner repository work order directory: %s', $targetDirectory));

                return Command::FAILURE;
            }
            file_put_contents($targetPath, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner repository work order written to %s.', $targetPath));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io->section('Owner repository work order');
        $io->writeln(sprintf('Next work mode: <info>%s</info>', $report->nextWorkMode));
        $io->writeln(sprintf('Owner repositories requested: <info>%d</info>', $report->ownerRepositoryCount()));
        $io->writeln(sprintf('Host application tracks: <info>%d</info>', $report->hostApplicationCount()));
        $io->writeln(sprintf('Missing transition artifacts: <comment>%d</comment>', $report->missingArtifactCount()));

        $io->section('Owner repository slices to request');
        $io->table(
            ['Component', 'Token', 'Expected archive', 'Repository path', 'Readiness'],
            array_map(static fn (array $item): array => [
                $item['componentKey'],
                $item['componentToken'],
                $item['expectedCurrentSliceArchive'],
                $item['expectedRepositoryPath'],
                $item['readinessFromReport'],
            ], $repositoryWorkOrders),
        );

        if ([] !== $hostApplicationWorkOrders) {
            $io->section('Host/post-application slice to request');
            $io->table(
                ['Repository', 'Expected archive', 'Track'],
                array_map(static fn (array $item): array => [
                    $item['repositoryKey'],
                    $item['expectedCurrentSliceArchive'],
                    $item['track'],
                ], $hostApplicationWorkOrders),
            );
        }

        $io->section('Recommended next actions');
        foreach ($recommendedNextActions as $action) {
            $io->writeln(' - '.$action);
        }

        return Command::SUCCESS;
    }

    /** @param mixed $components @return list<string> */
    /**
     * @return list<string>
     */
    private function normalizeComponents(mixed $components): array
    {
        if (!is_array($components)) {
            return [];
        }

        $normalized = [];
        foreach ($components as $component) {
            if (!is_string($component)) {
                continue;
            }
            $trimmed = trim($component);
            if ('' === $trimmed) {
                continue;
            }
            $normalized[] = $this->pascalToken($trimmed);
        }

        return array_values(array_unique($normalized));
    }

    /** @return array<string, array<string, mixed>> */
    /**
     * @param array<string, mixed> $patchReadiness
     *
     * @return array<string, array<string, mixed>>
     */
    private function readinessByComponent(?array $patchReadiness): array
    {
        $items = $patchReadiness['repositoryReadiness'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $indexed = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $componentKey = (string) ($item['componentKey'] ?? '');
            if ('' === $componentKey) {
                continue;
            }
            $indexed[$componentKey] = $item;
            $indexed[$this->componentToken($componentKey)] = $item;
        }

        return $indexed;
    }

    /** @return array<string, mixed>|null */
    private function loadJsonFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function componentToken(string $componentKey): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $this->pascalToken($componentKey)));
    }

    private function pascalToken(string $value): string
    {
        $parts = preg_split('/[^A-Za-z0-9]+/', $value) ?: [];
        $token = '';
        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }
            $token .= ucfirst($part);
        }

        return '' === $token ? $value : $token;
    }

    private function projectPath(string $path): string
    {
        if ('' === $path) {
            return $this->projectDir;
        }

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\/]/', $path)) {
            return $path;
        }

        return rtrim($this->projectDir, '/\\').'/'.ltrim($path, '/\\');
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return '' === $trimmed ? null : $trimmed;
    }
}
