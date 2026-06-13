<?php

declare(strict_types=1);

namespace App\Administering\Command;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Operation\AdministrationOperationReportProviderInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunFactoryInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationStatusRecorderInterface;
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
 * Runs one metadata-only Administering operation synchronously as a 3RC proof.
 *
 * The normal UI path persists a run and dispatches Messenger. This command keeps
 * the same persisted run/status/report/artifact path, but executes the runner in
 * process so operators and watchdog checks can prove the lifecycle without a
 * running async worker.
 */
#[AsCommand(
    name: 'administering:operation:lifecycle-proof',
    description: 'Executes a safe Administering operation synchronously and verifies run/event/artifact reporting.',
)]
final class AdministrationOperationLifecycleProofCommand extends Command
{
    public function __construct(
        private readonly AdministrationOperationRunFactoryInterface $operationRunFactory,
        private readonly AdministrationOperationRunnerInterface $operationRunner,
        private readonly AdministrationOperationStatusRecorderInterface $statusRecorder,
        private readonly AdministrationOperationReportProviderInterface $reportProvider,
        private readonly ManagerRegistry $managerRegistry,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('operation-type', null, InputOption::VALUE_REQUIRED, 'Launchable operation type to prove.', AdministrationOperationType::CONFIGURATION_SCAN)
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Safe target reference for the proof run.', 'administering:runtime-proof')
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
                    'proof' => 'administering_operation_lifecycle',
                    'mode' => 'synchronous_metadata_only',
                ],
            ));
            $operationKey = $operationRun->operationKey();
            $proof['operation_key'] = $operationKey;

            $this->statusRecorder->markRunning($operationKey);
            $result = $this->operationRunner->run($operationKey, $operationType);
            $this->statusRecorder->markFinished($operationKey, $result);

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

        $io->title('Administering operation lifecycle proof');
        $io->definitionList(
            ['operation type' => $proof['operation_type']],
            ['operation key' => (string) $proof['operation_key']],
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

        $success ? $io->success('Operation lifecycle proof passed.') : $io->warning('Operation lifecycle proof did not satisfy all RC checks.');

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

    /**
     * @param array<string, mixed> $proof
     *
     * @return array<string, mixed>
     */
    private function withReportChecks(array $proof, AdministrationOperationReport $report): array
    {
        $events = $report->events();
        $artifacts = $report->artifacts();

        $proof['status'] = $report->status();
        $proof['events'] = count($events);
        $proof['artifacts'] = count($artifacts);
        $proof['checks'] = [
            ['nameEntity' => 'run_status_terminal', 'ok' => in_array($report->status(), ['succeeded', 'skipped', 'failed'], true)],
            ['nameEntity' => 'events_written', 'ok' => count($events) >= 2],
            ['nameEntity' => 'artifact_written', 'ok' => count($artifacts) >= 1],
            ['nameEntity' => 'same_operation_key', 'ok' => $this->sameOperationKey($report)],
        ];

        foreach ($proof['checks'] as $check) {
            if (false === $check['ok']) {
                $proof['errors'][] = sprintf('Lifecycle check failed: %s.', $check['nameEntity']);
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
