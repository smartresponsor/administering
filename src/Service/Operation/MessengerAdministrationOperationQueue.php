<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\Message\AdministrationOperationRunMessage;
use App\Administering\ServiceInterface\Operation\AdministrationOperationQueueInterface;
use App\Administering\Value\Operation\AdministrationOperationDispatchResult;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerAdministrationOperationQueue implements AdministrationOperationQueueInterface
{
    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    public function dispatch(AdministrationOperationRun $operationRun): AdministrationOperationDispatchResult
    {
        $this->messageBus->dispatch(new AdministrationOperationRunMessage($operationRun->operationKey()));

        return AdministrationOperationDispatchResult::queued($operationRun->operationKey());
    }
}
