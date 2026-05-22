<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\Entity\AdministrationOperationEvent;
use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\ServiceInterface\Operation\AdministrationOperationStatusRecorderInterface;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Updates persisted operation runs and appends metadata-only operation events.
 */
final class DoctrineAdministrationOperationStatusRecorder implements AdministrationOperationStatusRecorderInterface
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public function markRunning(string $operationKey): void
    {
        $operationRun = $this->operationRun($operationKey);
        $operationRun?->markRunning();

        $this->persistEvent($operationKey, 'running', 'Operation execution started.', []);
    }

    public function markFinished(string $operationKey, AdministrationOperationExecutionResult $result): void
    {
        $operationRun = $this->operationRun($operationKey);
        if (null !== $operationRun) {
            if ($result->successful()) {
                $operationRun->markSucceeded();
            } else {
                $operationRun->markFailed($result->safeMessage());
            }
        }

        $this->persistEvent($operationKey, $result->status(), $result->safeMessage(), $result->safeContext());
    }

    public function markFailed(string $operationKey, \Throwable $throwable): void
    {
        $safeReason = sprintf('%s: %s', $throwable::class, $this->redact($throwable->getMessage()));
        $operationRun = $this->operationRun($operationKey);
        $operationRun?->markFailed($safeReason);

        $this->persistEvent($operationKey, 'failed', $safeReason, ['exception' => $throwable::class]);
    }

    private function operationRun(string $operationKey): ?AdministrationOperationRun
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);
        if (null === $manager) {
            return null;
        }

        $repository = $manager->getRepository(AdministrationOperationRun::class);
        $operationRun = $repository->findOneBy(['operationKey' => $operationKey]);

        return $operationRun instanceof AdministrationOperationRun ? $operationRun : null;
    }

    /** @param array<string, mixed> $safeContext */
    private function persistEvent(string $operationKey, string $status, string $safeMessage, array $safeContext): void
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationOperationEvent::class)
            ?? $this->managerRegistry->getManagerForClass(AdministrationOperationRun::class);

        if (null === $manager) {
            return;
        }

        $manager->persist(new AdministrationOperationEvent($operationKey, $status, $this->redact($safeMessage), $safeContext));
        $manager->flush();
    }

    private function redact(string $message): string
    {
        $message = preg_replace('/(secret|token|password|dsn|key)=([^\s]+)/i', '$1=***', $message) ?? $message;

        return mb_substr($message, 0, 500);
    }
}
