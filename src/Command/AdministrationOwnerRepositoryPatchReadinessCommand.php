<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Value\Admin\AdministrationOwnerRepositoryPatchReadinessReport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:owner-configuration-tools:owner-patch-readiness',
    description: 'Builds a read-only readiness report for moving from owner slice intake to concrete owner repository patch waves.',
)]
final class AdministrationOwnerRepositoryPatchReadinessCommand extends Command
{
    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('intake-json', null, InputOption::VALUE_REQUIRED, 'Owner repository slice intake JSON path.', 'delivery/patches/administering_owner_repository_slice_intake.json')
            ->addOption('transition-freeze-json', null, InputOption::VALUE_REQUIRED, 'Transition freeze JSON path.', 'delivery/patches/administering_owner_configuration_tool_transition_freeze.json')
            ->addOption('handoff-dir', null, InputOption::VALUE_REQUIRED, 'Generated owner-side external handoff directory.', 'delivery/owner-side-external-handoff')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print patch readiness report as JSON.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write patch readiness report JSON to this path.')
            ->addOption('allow-missing-artifacts', null, InputOption::VALUE_NONE, 'Allow missing transition artifacts for advisory runs.')
            ->addOption('fail-if-not-ready', null, InputOption::VALUE_NONE, 'Fail when any owner repository is not ready for a concrete patch wave.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $intakePath = $this->projectPath((string) $input->getOption('intake-json'));
        $freezePath = $this->projectPath((string) $input->getOption('transition-freeze-json'));
        $handoffDir = $this->projectPath((string) $input->getOption('handoff-dir'));

        $artifactChecks = [
            [
                'nameEntity' => 'owner repository slice intake report',
                'path' => $intakePath,
                'status' => is_file($intakePath) ? 'present' : 'missing',
            ],
            [
                'nameEntity' => 'transition freeze report',
                'path' => $freezePath,
                'status' => is_file($freezePath) ? 'present' : 'missing',
            ],
            [
                'nameEntity' => 'owner-side external handoff directory',
                'path' => $handoffDir,
                'status' => is_dir($handoffDir) ? 'present' : 'missing',
            ],
            [
                'nameEntity' => 'owner-side handoff report',
                'path' => rtrim($handoffDir, '/\\').'/handoff-report.json',
                'status' => is_file(rtrim($handoffDir, '/\\').'/handoff-report.json') ? 'present' : 'missing',
            ],
        ];

        $missingArtifactCount = count(array_filter($artifactChecks, static fn (array $item): bool => 'present' !== $item['status']));
        if ($missingArtifactCount > 0 && !(bool) $input->getOption('allow-missing-artifacts')) {
            $io->error('Required transition artifacts are missing. Re-run with --allow-missing-artifacts only for advisory reports.');
        }

        $intake = $this->loadJsonFile($intakePath, $io, 'owner repository slice intake report');
        if (null === $intake && !(bool) $input->getOption('allow-missing-artifacts')) {
            return Command::FAILURE;
        }

        $repositoryReadiness = [];
        foreach ($this->repositorySlices($intake) as $slice) {
            $componentKey = (string) ($slice['componentKey'] ?? 'unknown');
            $sliceStatus = (string) $slice['sliceStatus'];
            $isHost = 'host_application' === $componentKey;
            $patchMode = $isHost ? 'host_application_configuration_track' : 'owner_repository_overlay_track';
            $ready = 'available' === $sliceStatus && 0 === $missingArtifactCount;

            $repositoryReadiness[] = [
                'componentKey' => $componentKey,
                'repositoryName' => (string) ($slice['repositoryName'] ?? $componentKey),
                'expectedPath' => (string) ($slice['expectedPath'] ?? ''),
                'sliceStatus' => $sliceStatus,
                'patchMode' => $patchMode,
                'readyForConcretePatch' => $ready,
                'blockingReason' => $ready ? null : $this->blockingReason($sliceStatus, $missingArtifactCount),
                'recommendedNextAction' => $ready
                    ? 'Build a repository-specific touched patch from the current slice; do not apply generic external overlays blindly.'
                    : 'Provide/review required current slice and transition artifacts before building concrete patches.',
            ];
        }

        $readyCount = count(array_filter($repositoryReadiness, static fn (array $item): bool => true === $item['readyForConcretePatch']));
        $blockedCount = count($repositoryReadiness) - $readyCount;
        $readyForPatchWaves = 0 === $blockedCount && $readyCount > 0;
        $nextWorkMode = $readyForPatchWaves
            ? 'build_repository_specific_owner_patches'
            : 'complete_owner_slice_and_handoff_prerequisites';

        $report = new AdministrationOwnerRepositoryPatchReadinessReport(
            $artifactChecks,
            $repositoryReadiness,
            $readyForPatchWaves,
            $nextWorkMode,
        );

        $writeJson = $this->normalizeOptionalString($input->getOption('write-json'));
        if (null !== $writeJson) {
            $targetPath = $this->projectPath($writeJson);
            $targetDirectory = dirname($targetPath);
            if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
                $io->error(sprintf('Unable to create patch readiness report directory: %s', $targetDirectory));

                return Command::FAILURE;
            }
            file_put_contents($targetPath, json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Owner repository patch readiness report written to %s.', $targetPath));
        }

        $shouldFail = (bool) $input->getOption('fail-if-not-ready') && !$report->readyForPatchWaves;
        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($report->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $shouldFail ? Command::FAILURE : Command::SUCCESS;
        }

        $io->section('Owner repository patch readiness');
        $io->writeln(sprintf('Ready for concrete owner patch waves: <info>%s</info>', $report->readyForPatchWaves ? 'yes' : 'not yet'));
        $io->writeln(sprintf('Next work mode: <info>%s</info>', $report->nextWorkMode));
        $io->writeln(sprintf('Ready repositories: <info>%d</info>', $report->readyRepositoryCount()));
        $io->writeln(sprintf('Blocked repositories: <comment>%d</comment>', $report->blockedRepositoryCount()));

        $io->section('Transition artifacts');
        $io->table(
            ['Artifact', 'Status', 'Path'],
            array_map(static fn (array $item): array => [
                $item['nameEntity'],
                $item['status'],
                $item['path'],
            ], $artifactChecks),
        );

        $io->section('Repository readiness');
        $io->table(
            ['Component', 'Repository', 'Slice', 'Patch mode', 'Ready', 'Blocking reason'],
            array_map(static fn (array $item): array => [
                $item['componentKey'],
                $item['repositoryName'],
                $item['sliceStatus'],
                $item['patchMode'],
                true === $item['readyForConcretePatch'] ? 'yes' : 'no',
                $item['blockingReason'] ?? '',
            ], $repositoryReadiness),
        );

        return $shouldFail ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array<string, mixed>|null */
    private function loadJsonFile(string $path, SymfonyStyle $io, string $label): ?array
    {
        if (!is_file($path)) {
            $io->warning(sprintf('%s not found: %s', ucfirst($label), $path));

            return null;
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $io->error(sprintf('%s is invalid JSON: %s', ucfirst($label), $exception->getMessage()));

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /** @return list<array<string, mixed>> */
    /**
     * @param array<string, mixed> $intake
     *
     * @return list<array<string, mixed>>
     */
    private function repositorySlices(?array $intake): array
    {
        $slices = $intake['repositorySlices'] ?? [];

        return is_array($slices) ? array_values(array_filter($slices, 'is_array')) : [];
    }

    private function blockingReason(string $sliceStatus, int $missingArtifactCount): string
    {
        if ('available' !== $sliceStatus) {
            return 'repository current slice is not available';
        }

        if ($missingArtifactCount > 0) {
            return 'transition/handoff artifacts are missing';
        }

        return 'not ready';
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
