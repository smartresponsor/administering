<?php

declare(strict_types=1);

namespace App\Administering\Executor\Admin;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolExecutorInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolOpenGuardInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Dispatches persisted service-tool launches to concrete tool handlers.
 *
 * The dispatcher never treats a PHP file as executable merely because it was
 * indexed. A tool service must additionally be registered in the tagged handler
 * locator by implementing AdministrationServiceToolHandlerInterface.
 */
final readonly class AdministrationServiceToolExecutor implements AdministrationServiceToolExecutorInterface
{
    /** @param iterable<AdministrationServiceToolHandlerInterface> $toolHandlers */
    public function __construct(
        private AdministrationPersistenceRepository $persistenceRepository,
        private iterable $toolHandlers,
        private AdministrationServiceToolOpenGuardInterface $toolOpenGuard,
    ) {
    }

    public function execute(string $operationKey): AdministrationOperationExecutionResult
    {
        $operationRun = $this->operationRun($operationKey);

        try {
            $invocation = AdministrationServiceToolInvocation::fromSafeContext($operationKey, $operationRun->safeContext());
        } catch (\InvalidArgumentException $exception) {
            return AdministrationOperationExecutionResult::failed(
                'Service tool launch context is incomplete and cannot be dispatched.',
                ['operation_key' => $operationKey, 'reason' => $exception->getMessage()],
            );
        }

        try {
            $this->toolOpenGuard->assertInvocationCanExecute($invocation);
        } catch (\Throwable $exception) {
            return AdministrationOperationExecutionResult::failed(
                'Service tool launch context failed open/execute guard validation.',
                [
                    'operation_key' => $operationKey,
                    'tool_key' => $invocation->toolKey,
                    'service_class' => $invocation->serviceClass,
                    'source_ownership' => $invocation->sourceOwnership,
                    'reason' => $exception->getMessage(),
                ],
            );
        }

        $handler = $this->handlerFor($invocation->serviceClass);
        if (null === $handler) {
            return AdministrationOperationExecutionResult::skipped(
                'Service tool was indexed and submitted, but no executable tool handler is registered for its service class.',
                [
                    'operation_key' => $operationKey,
                    'tool_key' => $invocation->toolKey,
                    'service_class' => $invocation->serviceClass,
                    'source_ownership' => $invocation->sourceOwnership,
                    'owner_component_key' => $invocation->ownerComponentKey,
                    'required_contract' => AdministrationServiceToolHandlerInterface::class,
                ],
            );
        }

        return $handler->handleAdministrationServiceTool($invocation);
    }

    private function handlerFor(string $serviceClass): ?AdministrationServiceToolHandlerInterface
    {
        foreach ($this->toolHandlers as $handler) {
            if ($handler::class === $serviceClass) {
                return $handler;
            }
        }

        return null;
    }

    private function operationRun(string $operationKey): AdministrationOperationRunEntity
    {
        $operationRun = $this->persistenceRepository->findOneBy(AdministrationOperationRunEntity::class, ['operationKey' => $operationKey]);

        if (!$operationRun instanceof AdministrationOperationRunEntity) {
            throw new \RuntimeException(sprintf('Administering operation run "%s" was not found in system storage.', $operationKey));
        }

        return $operationRun;
    }
}
