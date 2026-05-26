<?php

declare(strict_types=1);

namespace App\Administering\Factory\Operation;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Accessing\AdministrationCurrentUserContextProviderInterface;
use App\Administering\ServiceInterface\Operation\AdministrationOperationRunFactoryInterface;
use App\Administering\Value\Operation\AdministrationOperationPlan;

final class AdministrationOperationRunFactory implements AdministrationOperationRunFactoryInterface
{
    public function __construct(private readonly AdministrationCurrentUserContextProviderInterface $currentUserContextProvider)
    {
    }

    public function createForCurrentUser(AdministrationOperationPlan $plan): AdministrationOperationRun
    {
        $current = $this->currentUserContextProvider->current();
        $subjectIdentifier = null === $current ? 'anonymous' : $current->subjectIdentifier();

        return new AdministrationOperationRun(
            $this->newOperationKey($plan->operationType()),
            $plan->operationType(),
            $subjectIdentifier,
            $plan->targetReference(),
            $plan->safeContext(),
        );
    }

    private function newOperationKey(string $operationType): string
    {
        return sprintf('%s-%s-%s', $operationType, gmdate('YmdHis'), bin2hex(random_bytes(6)));
    }
}
