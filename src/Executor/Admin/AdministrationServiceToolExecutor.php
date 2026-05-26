<?php

declare(strict_types=1);

namespace App\Administering\Executor\Admin;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolExecutorInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolOpenGuardInterface;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;

/**
 * Dispatches persisted service-tool launches to concrete tool handlers.
 *
 * The dispatcher never treats a PHP file as executable merely because it was
 * indexed. A tool service must additionally be registered in the tagged handler
 * locator by implementing AdministrationServiceToolHandlerInterface.
 */
final readonly class AdministrationServiceToolExecutor implements AdministrationServiceToolExecutorInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ContainerInterface $toolHandlers,
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

        if (!$this->toolHandlers->has($invocation->serviceClass)) {
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

        $handler = $this->toolHandlers->get($invocation->serviceClass);
        if (!$handler instanceof AdministrationServiceToolHandlerInterface) {
            return AdministrationOperationExecutionResult::failed(
                'Registered service tool handler does not implement the required execution contract.',
                [
                    'operation_key' => $operationKey,
                    'tool_key' => $invocation->toolKey,
                    'service_class' => $invocation->serviceClass,
                    'required_contract' => AdministrationServiceToolHandlerInterface::class,
                    'actual_type' => get_debug_type($handler),
                ],
            );
        }

        return $handler->handleAdministrationServiceTool($invocation);
    }

    private function operationRun(string $operationKey): AdministrationOperationRun
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

        return $operationRun;
    }
}
