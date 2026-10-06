<?php

declare(strict_types=1);

namespace App\Administering\Submitter\Operation;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Operation\AdministrationOperationQueueInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunFactoryInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationSubmitterInterface;
use App\Administering\Value\Operation\AdministrationOperationPlan;

/**
 * Persists operation runs through the manager assigned to Administering entities,
 * then dispatches a metadata-only Messenger message.
 */
final class AdministrationOperationSubmitter implements AdministrationOperationSubmitterInterface
{
    public function __construct(
        private readonly AdministrationOperationRunFactoryInterface $operationRunFactory,
        private readonly AdministrationOperationQueueInterface $operationQueue,
        private readonly AdministrationPersistenceRepository $persistenceRepository,
    ) {
    }

    public function submitForCurrentUser(AdministrationOperationPlan $plan): AdministrationOperationRunEntity
    {
        $operationRun = $this->operationRunFactory->createForCurrentUser($plan);
        $this->persistenceRepository->persist($operationRun);

        $this->operationQueue->dispatch($operationRun);

        return $operationRun;
    }
}
