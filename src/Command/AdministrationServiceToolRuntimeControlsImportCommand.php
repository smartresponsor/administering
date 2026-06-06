<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ServiceInterface\Audit\AdministrationAuditRecorderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:runtime-controls:import',
    description: 'Imports SQLite-owned service-tool runtime controls from a reviewed export without changing filesystem-derived tool identity.',
)]
final class AdministrationServiceToolRuntimeControlsImportCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly AdministrationAuditRecorderInterface $auditRecorder,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Runtime controls export JSON file path.')
            ->addOption('section', null, InputOption::VALUE_REQUIRED, 'Import only a section/direction key, for example Connected or connected.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate and preview changes without writing them.')
            ->addOption('allow-missing', null, InputOption::VALUE_NONE, 'Do not fail when an exported toolKey is not present in the current SQLite index.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print import result as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = trim((string) $input->getArgument('file'));
        $section = $this->normalizeOptionalSection($input->getOption('section'));
        $dryRun = (bool) $input->getOption('dry-run');
        $allowMissing = (bool) $input->getOption('allow-missing');

        if ('' === $file || !is_file($file)) {
            $io->error(sprintf('Runtime controls import file was not found: %s', $file));

            return Command::INVALID;
        }

        $payload = $this->readJsonPayload($file, $io);
        if (null === $payload) {
            return Command::INVALID;
        }

        if (($payload['schema'] ?? null) !== 'administering.service_tool_runtime_controls.v1') {
            $io->error('Unsupported runtime controls import schema. Expected administering.service_tool_runtime_controls.v1.');

            return Command::INVALID;
        }

        $controls = $payload['controls'] ?? null;
        if (!is_array($controls)) {
            $io->error('Runtime controls import payload must contain a controls array.');

            return Command::INVALID;
        }

        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            $io->error('No Doctrine entity manager is configured for AdministrationServiceToolRecord.');

            return Command::FAILURE;
        }

        /** @var array<string, array<string, mixed>> $changes */
        $changes = [];
        /** @var list<string> $missing */
        $missing = [];
        /** @var list<string> $invalid */
        $invalid = [];
        /** @var list<array<string, mixed>> $applied */
        $applied = [];

        foreach ($controls as $index => $control) {
            if (!is_array($control)) {
                $invalid[] = sprintf('controls[%d] must be an object.', $index);
                continue;
            }

            $toolKey = $control['toolKey'] ?? null;
            if (!is_string($toolKey) || '' === trim($toolKey)) {
                $invalid[] = sprintf('controls[%d].toolKey must be a non-empty string.', $index);
                continue;
            }

            $toolKey = trim($toolKey);
            $controlSection = $control['sectionKey'] ?? null;
            if (null !== $section && is_string($controlSection) && $section !== $this->normalizeOptionalSection($controlSection)) {
                continue;
            }

            $enabled = $this->readBoolean($control, 'enabled', $invalid, $toolKey);
            $visible = $this->readBoolean($control, 'visible', $invalid, $toolKey);
            $position = $this->readInteger($control, 'position', $invalid, $toolKey);
            $labelOverride = $this->readNullableLabel($control, 'labelOverride', $invalid, $toolKey);
            if (!empty($invalid) && str_starts_with((string) end($invalid), $toolKey.':')) {
                continue;
            }

            /** @var AdministrationServiceToolRecord|null $record */
            $record = $manager->getRepository(AdministrationServiceToolRecord::class)->findOneBy(['toolKey' => $toolKey]);
            if (!$record instanceof AdministrationServiceToolRecord) {
                $missing[] = $toolKey;
                continue;
            }

            $before = [
                'enabled' => $record->isEnabled(),
                'visible' => $record->isVisible(),
                'position' => $record->getPosition(),
                'labelOverride' => $record->getLabelOverride(),
                'displayLabel' => $record->getDisplayLabel(),
            ];

            $after = [
                'enabled' => $enabled,
                'visible' => $visible,
                'position' => $position,
                'labelOverride' => $labelOverride,
                'displayLabel' => is_string($labelOverride) && '' !== trim($labelOverride) ? trim($labelOverride) : $record->getGeneratedLabel(),
            ];

            if ($before === $after) {
                continue;
            }

            $changes[$toolKey] = [
                'toolKey' => $toolKey,
                'sectionKey' => $record->getSectionKey(),
                'toolSlug' => $record->getToolSlug(),
                'before' => $before,
                'after' => $after,
            ];

            if (!$dryRun) {
                $record->configureRuntimeControls($enabled, $visible, $position, $labelOverride, null === $labelOverride);
                $applied[] = $changes[$toolKey];
            }
        }

        if ([] !== $invalid) {
            $io->error('Runtime controls import payload contains invalid records.');
            foreach ($invalid as $issue) {
                $io->writeln(sprintf(' - %s', $issue));
            }

            return Command::INVALID;
        }

        if ([] !== $missing && !$allowMissing) {
            $io->error('Some imported tool keys are not present in the current SQLite index. Run refresh-index or pass --allow-missing.');
            foreach ($missing as $toolKey) {
                $io->writeln(sprintf(' - %s', $toolKey));
            }

            return Command::FAILURE;
        }

        if (!$dryRun) {
            $manager->flush();

            foreach ($applied as $change) {
                $this->auditRecorder->record('administration.service_tool.runtime_controls.imported', (string) $change['toolKey'], [
                    'source' => 'console_import',
                    'sectionKey' => $change['sectionKey'],
                    'toolSlug' => $change['toolSlug'],
                    'before' => $change['before'],
                    'after' => $change['after'],
                    'importFile' => $file,
                ]);
            }
        }

        $result = [
            'schema' => 'administering.service_tool_runtime_controls_import_result.v1',
            'dryRun' => $dryRun,
            'section' => $section,
            'changedCount' => count($changes),
            'missingCount' => count($missing),
            'missingToolKeys' => $missing,
            'changes' => array_values($changes),
        ];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io->title($dryRun ? 'Service-tool runtime controls import dry-run' : 'Service-tool runtime controls import');
        $io->text(sprintf('Changed records: %d', count($changes)));
        if ([] !== $missing) {
            $io->warning(sprintf('Missing records ignored: %d', count($missing)));
        }

        if ([] !== $changes) {
            $io->table(
                ['Tool key', 'Before', 'After'],
                array_map(static fn (array $change): array => [
                    $change['toolKey'],
                    json_encode($change['before'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                    json_encode($change['after'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                ], array_values($changes)),
            );
        }

        $io->success($dryRun ? 'Runtime controls import dry-run completed.' : 'Runtime controls imported.');

        return Command::SUCCESS;
    }

    /** @return array<string, mixed>|null */
    private function readJsonPayload(string $file, SymfonyStyle $io): ?array
    {
        try {
            $payload = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $io->error(sprintf('Unable to parse JSON import file: %s', $exception->getMessage()));

            return null;
        }

        if (!is_array($payload)) {
            $io->error('Runtime controls import file must contain a JSON object.');

            return null;
        }

        return $payload;
    }

    /** @param array<string, mixed> $control @param list<string> $invalid */
    /**
     * @param array<string, mixed> $control
     * @param list<string>         $invalid
     */
    private function readBoolean(array $control, string $field, array &$invalid, string $toolKey): bool
    {
        $value = $control[$field] ?? null;
        if (!is_bool($value)) {
            $invalid[] = sprintf('%s: %s must be boolean.', $toolKey, $field);

            return false;
        }

        return $value;
    }

    /** @param array<string, mixed> $control @param list<string> $invalid */
    /**
     * @param array<string, mixed> $control
     * @param list<string>         $invalid
     */
    private function readInteger(array $control, string $field, array &$invalid, string $toolKey): int
    {
        $value = $control[$field] ?? null;
        if (!is_int($value)) {
            $invalid[] = sprintf('%s: %s must be integer.', $toolKey, $field);

            return 0;
        }

        return $value;
    }

    /** @param array<string, mixed> $control @param list<string> $invalid */
    /**
     * @param array<string, mixed> $control
     * @param list<string>         $invalid
     */
    private function readNullableLabel(array $control, string $field, array &$invalid, string $toolKey): ?string
    {
        $value = $control[$field] ?? null;
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            $invalid[] = sprintf('%s: %s must be string or null.', $toolKey, $field);

            return null;
        }

        $label = trim($value);
        if ('' === $label) {
            return null;
        }

        if (180 < mb_strlen($label)) {
            $invalid[] = sprintf('%s: %s must be 180 characters or fewer.', $toolKey, $field);

            return null;
        }

        return $label;
    }

    private function normalizeOptionalSection(mixed $section): ?string
    {
        if (!is_string($section)) {
            return null;
        }

        $section = trim($section);
        if ('' === $section) {
            return null;
        }

        return ucfirst(strtolower($section));
    }
}
