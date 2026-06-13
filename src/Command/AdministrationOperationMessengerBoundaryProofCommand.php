<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\Message\AdministrationOperationRunMessage;
use App\Administering\MessageHandler\AdministrationOperationRunMessageHandler;
use App\Administering\ServiceInterface\Operation\AdministrationOperationReportProviderInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunFactoryInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\Value\Operation\AdministrationOperationPlan;
use App\Administering\Value\Operation\AdministrationOperationReport;
use App\Administering\Value\Operation\AdministrationOperationType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Proves the Messenger handler boundary without requiring a running worker.
 *
 * The command persists an operation run, creates the same metadata-only message
 * that the queue dispatches, and invokes the message handler directly. This
 * catches drift between the queued operation key, persisted operation type,
 * runner support, status recorder, events, artifacts, and report provider.
 */
#[AsCommand(
    name: 'administering:operation:messenger-boundary-proof',
    description: 'Persists an Administering operation and proves the Messenger message-handler boundary in-process.',
)]
final class AdministrationOperationMessengerBoundaryProofCommand extends Command
{
    public function __construct(
        private readonly AdministrationOperationRunFactoryInterface $operationRunFactory,
        private readonly AdministrationOperationRunnerInterface $operationRunner,
        private readonly AdministrationOperationRunMessageHandler $messageHandler,
        private readonly AdministrationOperationReportProviderInterface $reportProvider,
        private readonly ManagerRegistry $managerRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('operation-type', null, InputOption::VALUE_REQUIRED, 'Launchable operation type to prove through the Messenger handler boundary.', AdministrationOperationType::CONFIGURATION_SCAN)
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Safe target reference for the proof run.', 'administering:messenger-boundary-proof')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit machine-readable JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $operationType = trim((string) $input->getOption('operation-type'));
        $targetReference = trim((string) $input->getOption('target'));

        $proof = [
            'operation_type' => $operationType,
            'target_reference' => $targetReference,
            'operation_key' => null,
            'message_class' => AdministrationOperationRunMessage::class,
            'message_payload_fields' => $this->messagePayloadFields(),
            'status' => 'not_started',
            'events' => 0,
            'artifacts' => 0,
            'checks' => [],
            'errors' => [],
        ];

        try {
            $this->assertLaunchable($operationType);

            $operationRun = $this->persistProofRun(new AdministrationOperationPlan(
                $operationType,
                $targetReference,
                [
                    'proof' => 'administering_operation_messenger_boundary',
                    'mode' => 'in_process_handler_invocation',
                ],
            ));
            $operationKey = $operationRun->operationKey();
            $proof['operation_key'] = $operationKey;

            ($this->messageHandler)(new AdministrationOperationRunMessage($operationKey));

            $report = $this->reportProvider->reportFor($operationKey);
            $proof = $this->withReportChecks($proof, $report);
        } catch (\Throwable $throwable) {
            $proof['errors'][] = sprintf('%s: %s', $throwable::class, $this->redact($throwable->getMessage()));
        }

        $success = [] === $proof['errors']
            && in_array($proof['status'], ['succeeded', 'skipped'], true)
            && $proof['events'] >= 2
            && $proof['artifacts'] >= 1;

        $proof['successful'] = $success;

        if ((bool) $input->getOption('json')) {
            $output->writeln((string) json_encode($proof, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $success ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Administering operation Messenger boundary proof');
        $io->definitionList(
            ['operation type' => $proof['operation_type']],
            ['operation key' => (string) $proof['operation_key']],
            ['message class' => $proof['message_class']],
            ['message payload fields' => implode(', ', $proof['message_payload_fields'])],
            ['status' => $proof['status']],
            ['events' => (string) $proof['events']],
            ['artifacts' => (string) $proof['artifacts']],
        );

        if ([] !== $proof['checks']) {
            $io->section('Checks');
            foreach ($proof['checks'] as $check) {
                $io->writeln(sprintf('- %s: %s', $check['nameEntity'], $check['ok'] ? 'ok' : 'failed'));
            }
        }

        if ([] !== $proof['errors']) {
            $io->error($proof['errors']);

            return Command::FAILURE;
        }

        $success ? $io->success('Messenger boundary proof passed.') : $io->warning('Messenger boundary proof did not satisfy all RC checks.');

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    private function assertLaunchable(string $operationType): void
    {
        if (!AdministrationOperationType::isLaunchable($operationType)) {
            throw new \InvalidArgumentException(sprintf('Operation type "%s" is not launchable.', $operationType));
        }

        if (!in_array($operationType, $this->operationRunner->supportedOperationTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('Operation type "%s" is not supported by the configured runner.', $operationType));
        }
    }

    private function persistProofRun(AdministrationOperationPlan $plan): AdministrationOperationRun
    {
        $operationRun = $this->operationRunFactory->createForCurrentUser($plan);
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering operation runs. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        $manager->persist($operationRun);
        $manager->flush();

        return $operationRun;
    }

    /** @return list<string> */
    private function messagePayloadFields(): array
    {
        $reflection = new \ReflectionClass(AdministrationOperationRunMessage::class);

        return array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            $reflection->getProperties(),
        );
    }

    /**
     * @param array<string, mixed> $proof
     *
     * @return array<string, mixed>
     */
    private function withReportChecks(array $proof, AdministrationOperationReport $report): array
    {
        $events = $report->events();
        $artifacts = $report->artifacts();
        $messageFields = $proof['message_payload_fields'];

        $proof['status'] = $report->status();
        $proof['events'] = count($events);
        $proof['artifacts'] = count($artifacts);
        $proof['checks'] = [
            ['nameEntity' => 'message_payload_only_operation_key', 'ok' => ['operationKey'] === $messageFields],
            ['nameEntity' => 'run_status_terminal', 'ok' => in_array($report->status(), ['succeeded', 'skipped', 'failed'], true)],
            ['nameEntity' => 'events_written', 'ok' => count($events) >= 2],
            ['nameEntity' => 'artifact_written', 'ok' => count($artifacts) >= 1],
            ['nameEntity' => 'same_operation_key', 'ok' => $this->sameOperationKey($report)],
        ];

        foreach ($proof['checks'] as $check) {
            if (false === $check['ok']) {
                $proof['errors'][] = sprintf('Messenger boundary check failed: %s.', $check['nameEntity']);
            }
        }

        return $proof;
    }

    private function sameOperationKey(AdministrationOperationReport $report): bool
    {
        foreach ($report->artifacts() as $artifact) {
            $context = $artifact['safe_context'] ?? [];
            if (is_array($context) && isset($context['operation_key']) && $context['operation_key'] !== $report->operationKey()) {
                return false;
            }
        }

        return true;
    }

    private function redact(string $message): string
    {
        $message = preg_replace('/(secret|token|password|credential|private|authorization|dsn|key)=([^\s]+)/i', '$1=***', $message) ?? $message;

        return mb_substr($message, 0, 500);
    }
}
