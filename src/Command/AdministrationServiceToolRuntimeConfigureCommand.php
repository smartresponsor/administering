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
    name: 'administering:service-tools:runtime-configure',
    description: 'Updates runtime controls for a materialized service-tool record without changing scanned filesystem identity.',
)]
final class AdministrationServiceToolRuntimeConfigureCommand extends Command
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
            ->addArgument('toolKey', InputArgument::REQUIRED, 'Tool key, for example connected.component_readiness_report.')
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Mark the tool as enabled.')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Mark the tool as disabled.')
            ->addOption('show', null, InputOption::VALUE_NONE, 'Mark the tool as visible in the admin surface.')
            ->addOption('hide', null, InputOption::VALUE_NONE, 'Mark the tool as hidden from the admin surface.')
            ->addOption('position', null, InputOption::VALUE_REQUIRED, 'Set the runtime menu/index position integer.')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Set an optional runtime display label override.')
            ->addOption('clear-label', null, InputOption::VALUE_NONE, 'Clear the runtime display label override and use the generated label again.')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print the updated runtime controls as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $toolKey = trim((string) $input->getArgument('toolKey'));

        if ('' === $toolKey) {
            $io->error('Tool key must not be blank.');

            return Command::INVALID;
        }

        $enabled = $this->resolveNullableBooleanOption($input, 'enable', 'disable', $io);
        if ('invalid' === $enabled) {
            return Command::INVALID;
        }

        $visible = $this->resolveNullableBooleanOption($input, 'show', 'hide', $io);
        if ('invalid' === $visible) {
            return Command::INVALID;
        }

        $position = $this->resolveNullablePosition($input, $io);
        if ('invalid' === $position) {
            return Command::INVALID;
        }

        $labelOverride = $this->resolveNullableLabelOverride($input, $io);
        if ('invalid' === $labelOverride) {
            return Command::INVALID;
        }

        $clearLabelOverride = (bool) $input->getOption('clear-label');
        if ($clearLabelOverride && null !== $labelOverride) {
            $io->error('Options --label and --clear-label cannot be used together.');

            return Command::INVALID;
        }

        if (null === $enabled && null === $visible && null === $position && null === $labelOverride && !$clearLabelOverride) {
            $io->warning('No runtime controls were requested. Use --enable/--disable, --show/--hide, --position, --label, or --clear-label.');

            return Command::INVALID;
        }

        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            $io->error('No Doctrine entity manager is configured for AdministrationServiceToolRecord.');

            return Command::FAILURE;
        }

        $record = $manager->getRepository(AdministrationServiceToolRecord::class)->findOneBy(['toolKey' => $toolKey]);
        if (!$record instanceof AdministrationServiceToolRecord) {
            $io->error(sprintf('Service-tool record "%s" was not found. Run administering:service-tools:refresh-index first.', $toolKey));

            return Command::FAILURE;
        }

        $before = [
            'enabled' => $record->isEnabled(),
            'visible' => $record->isVisible(),
            'position' => $record->getPosition(),
            'labelOverride' => $record->getLabelOverride(),
            'displayLabel' => $record->getDisplayLabel(),
        ];

        /* @var bool|null $enabled */
        /* @var bool|null $visible */
        /* @var int|null $position */
        /* @var string|null $labelOverride */
        $record->configureRuntimeControls($enabled, $visible, $position, $labelOverride, $clearLabelOverride);
        $manager->flush();

        $after = [
            'enabled' => $record->isEnabled(),
            'visible' => $record->isVisible(),
            'position' => $record->getPosition(),
            'labelOverride' => $record->getLabelOverride(),
            'displayLabel' => $record->getDisplayLabel(),
        ];

        $this->auditRecorder->record('administration.service_tool.runtime_controls.updated', $record->getToolKey(), [
            'source' => 'console',
            'sectionKey' => $record->getSectionKey(),
            'toolSlug' => $record->getToolSlug(),
            'before' => $before,
            'after' => $after,
            'requested' => [
                'enabled' => $enabled,
                'visible' => $visible,
                'position' => $position,
                'labelOverride' => $labelOverride,
                'clearLabelOverride' => $clearLabelOverride,
            ],
        ]);

        $payload = [
            'toolKey' => $record->getToolKey(),
            'sectionKey' => $record->getSectionKey(),
            'enabled' => $record->isEnabled(),
            'visible' => $record->isVisible(),
            'position' => $record->getPosition(),
            'generatedLabel' => $record->getGeneratedLabel(),
            'labelOverride' => $record->getLabelOverride(),
            'displayLabel' => $record->getDisplayLabel(),
            'openable' => $record->isOpenable(),
            'runnable' => $record->isRunnable(),
        ];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $io->success(sprintf('Runtime controls updated for %s.', $record->getToolKey()));
        $io->table(
            ['Tool key', 'Display label', 'Enabled', 'Visible', 'Position', 'Openable', 'Runnable'],
            [[
                $payload['toolKey'],
                $payload['displayLabel'],
                $payload['enabled'] ? 'yes' : 'no',
                $payload['visible'] ? 'yes' : 'no',
                (string) $payload['position'],
                $payload['openable'] ? 'yes' : 'no',
                $payload['runnable'] ? 'yes' : 'no',
            ]],
        );

        return Command::SUCCESS;
    }

    /** @return bool|'invalid'|null */
    private function resolveNullableBooleanOption(InputInterface $input, string $positiveOption, string $negativeOption, SymfonyStyle $io): bool|string|null
    {
        $positive = (bool) $input->getOption($positiveOption);
        $negative = (bool) $input->getOption($negativeOption);

        if ($positive && $negative) {
            $io->error(sprintf('Options --%s and --%s cannot be used together.', $positiveOption, $negativeOption));

            return 'invalid';
        }

        if ($positive) {
            return true;
        }

        if ($negative) {
            return false;
        }

        return null;
    }

    /** @return int|'invalid'|null */
    private function resolveNullablePosition(InputInterface $input, SymfonyStyle $io): int|string|null
    {
        $rawPosition = $input->getOption('position');
        if (null === $rawPosition) {
            return null;
        }

        if (!is_string($rawPosition) || !preg_match('/^-?\d+$/', $rawPosition)) {
            $io->error('Position must be an integer.');

            return 'invalid';
        }

        return (int) $rawPosition;
    }

    /** @return string|'invalid'|null */
    private function resolveNullableLabelOverride(InputInterface $input, SymfonyStyle $io): ?string
    {
        $rawLabel = $input->getOption('label');
        if (null === $rawLabel) {
            return null;
        }

        if (!is_string($rawLabel)) {
            $io->error('Label override must be a string.');

            return 'invalid';
        }

        $label = trim($rawLabel);
        if ('' === $label) {
            $io->error('Label override must not be blank. Use --clear-label to remove the override.');

            return 'invalid';
        }

        if (180 < mb_strlen($label)) {
            $io->error('Label override must be 180 characters or fewer.');

            return 'invalid';
        }

        return $label;
    }
}
