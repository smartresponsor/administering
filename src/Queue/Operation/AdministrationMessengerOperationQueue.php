<?php

declare(strict_types=1);

namespace App\Administering\Queue\Operation;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Message\AdministrationOperationRunMessage;
use App\Administering\ServiceInterface\Operation\AdministrationOperationQueueInterface;
use App\Administering\Value\Operation\AdministrationOperationDispatchResult;
use Symfony\Component\Messenger\MessageBusInterface;

final class AdministrationMessengerOperationQueue implements AdministrationOperationQueueInterface
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    public function dispatch(AdministrationOperationRunEntity $operationRun): AdministrationOperationDispatchResult
    {
        $this->messageBus->dispatch(new AdministrationOperationRunMessage($operationRun->operationKey()));

        return AdministrationOperationDispatchResult::queued($operationRun->operationKey());
    }
}
