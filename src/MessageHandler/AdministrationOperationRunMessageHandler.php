<?php

declare(strict_types=1);

namespace App\Administering\MessageHandler;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\Message\AdministrationOperationRunMessage;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationStatusRecorderInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Worker boundary for persisted Administering operations.
 *
 * Messenger carries only an operation key. The status recorder loads and updates
 * the persisted run in system storage while preserving the no-secrets-in-queue rule.
 */
final class AdministrationOperationRunMessageHandler
{
    public function __construct(
        private readonly AdministrationOperationRunnerInterface $operationRunner,
        private readonly AdministrationOperationStatusRecorderInterface $statusRecorder,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function __invoke(AdministrationOperationRunMessage $message): void
    {
        $operationKey = $message->operationKey();
        $this->statusRecorder->markRunning($operationKey);

        try {
            $operationType = $this->operationTypeForKey($operationKey);
            $result = $this->operationRunner->run($operationKey, $operationType);
            $this->statusRecorder->markFinished($operationKey, $result);
        } catch (\Throwable $throwable) {
            $this->statusRecorder->markFailed($operationKey, $throwable);

            throw $throwable;
        }
    }

    private function operationTypeForKey(string $operationKey): string
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering operation runs. Configure the system SQLite entity manager for App\\Administering entities.');
        }

        $operationRun = $manager
            ->getRepository(AdministrationOperationRun::class)
            ->findOneBy(['operationKey' => $operationKey]);

        if (!$operationRun instanceof AdministrationOperationRun) {
            throw new \RuntimeException(sprintf('Administering operation run "%s" was not found in system storage.', $operationKey));
        }

        return $operationRun->operationType();
    }
}
