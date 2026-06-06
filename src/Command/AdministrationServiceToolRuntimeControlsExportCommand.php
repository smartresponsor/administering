<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Entity\AdministrationServiceToolRecord;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'administering:service-tools:runtime-controls:export',
    description: 'Exports SQLite-owned service-tool runtime controls without exporting scanned filesystem identity as editable configuration.',
)]
final class AdministrationServiceToolRuntimeControlsExportCommand extends Command
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('section', InputArgument::OPTIONAL, 'Optional section/direction key, for example Connected or connected.')
            ->addOption('write-json', null, InputOption::VALUE_REQUIRED, 'Write the export payload to a JSON file path.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the full export payload as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $section = $this->normalizeOptionalSection($input->getArgument('section'));

        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            $io->error('No Doctrine entity manager is configured for AdministrationServiceToolRecord.');

            return Command::FAILURE;
        }

        $criteria = [];
        if (null !== $section) {
            $criteria['sectionKey'] = $section;
        }

        /** @var list<AdministrationServiceToolRecord> $records */
        $records = $manager->getRepository(AdministrationServiceToolRecord::class)->findBy($criteria, [
            'sectionKey' => 'ASC',
            'position' => 'ASC',
            'toolKey' => 'ASC',
        ]);

        $payload = [
            'schema' => 'administering.service_tool_runtime_controls.v1',
            'exportedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'section' => $section,
            'count' => count($records),
            'controls' => array_map(static fn (AdministrationServiceToolRecord $record): array => [
                'toolKey' => $record->getToolKey(),
                'sectionKey' => $record->getSectionKey(),
                'toolSlug' => $record->getToolSlug(),
                'enabled' => $record->isEnabled(),
                'visible' => $record->isVisible(),
                'position' => $record->getPosition(),
                'generatedLabel' => $record->getGeneratedLabel(),
                'labelOverride' => $record->getLabelOverride(),
                'displayLabel' => $record->getDisplayLabel(),
                'openable' => $record->isOpenable(),
                'runnable' => $record->isRunnable(),
                'checksum' => $record->getChecksum(),
                'serviceClass' => $record->getServiceClass(),
            ], $records),
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
                $io->error(sprintf('Unable to create export directory: %s', $targetDirectory));

                return Command::FAILURE;
            }

            file_put_contents($targetPath, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $io->success(sprintf('Runtime controls exported to %s.', $targetPath));
        }

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io->title('Service-tool runtime controls export');
        $io->text(sprintf('Records: %d', count($records)));
        if (null !== $section) {
            $io->text(sprintf('Section: %s', $section));
        }

        $io->table(
            ['Tool key', 'Display label', 'Enabled', 'Visible', 'Position', 'Openable', 'Runnable'],
            array_map(static fn (AdministrationServiceToolRecord $record): array => [
                $record->getToolKey(),
                $record->getDisplayLabel(),
                $record->isEnabled() ? 'yes' : 'no',
                $record->isVisible() ? 'yes' : 'no',
                (string) $record->getPosition(),
                $record->isOpenable() ? 'yes' : 'no',
                $record->isRunnable() ? 'yes' : 'no',
            ], $records),
        );

        return Command::SUCCESS;
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
