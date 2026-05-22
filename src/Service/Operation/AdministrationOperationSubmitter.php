<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Operation\AdministrationOperationQueueInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunFactoryInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationSubmitterInterface;
use App\Administering\Value\Operation\AdministrationOperationPlan;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Persists operation runs through the manager assigned to Administering entities,
 * then dispatches a metadata-only Messenger message.
 */
final class AdministrationOperationSubmitter implements AdministrationOperationSubmitterInterface
{
    public function __construct(
        private readonly AdministrationOperationRunFactoryInterface $operationRunFactory,
        private readonly AdministrationOperationQueueInterface $operationQueue,
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function submitForCurrentUser(AdministrationOperationPlan $plan): AdministrationOperationRun
    {
        $operationRun = $this->operationRunFactory->createForCurrentUser($plan);
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);

        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering operation runs. Configure the system SQLite entity manager for App\Administering entities.');
        }

        $manager->persist($operationRun);
        $manager->flush();

        $this->operationQueue->dispatch($operationRun);

        return $operationRun;
    }
}
