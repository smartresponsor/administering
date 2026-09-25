<?php

declare(strict_types=1);

namespace App\Administering\Handler;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Message\AdministrationOperationRunMessage;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunnerInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationStatusRecorderInterface;

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
        private readonly AdministrationPersistenceRepository $persistenceRepository,
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
        $operationRun = $this->persistenceRepository->findOneBy(AdministrationOperationRunEntity::class, ['operationKey' => $operationKey]);

        if (!$operationRun instanceof AdministrationOperationRunEntity) {
            throw new \RuntimeException(sprintf('Administering operation run "%s" was not found in system storage.', $operationKey));
        }

        return $operationRun->operationType();
    }
}
