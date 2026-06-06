<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tracks long-running administrative operations without storing sensitive payloads.
 *
 * Examples include configuration scans, credential presence checks, composer validation,
 * generated patch builds, and connected-component evidence refreshes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_operation_run')]
#[ORM\Index(name: 'idx_administration_operation_run_type_status', columns: ['operation_type', 'status'])]
#[ORM\Index(name: 'idx_administration_operation_run_subject', columns: ['subject_identifier'])]
class AdministrationOperationRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'operation_key', type: 'string', length: 180, unique: true)]
    private string $operationKey;

    #[ORM\Column(name: 'operation_type', type: 'string', length: 80)]
    private string $operationType;

    #[ORM\Column(name: 'status', type: 'string', length: 40)]
    private string $status = 'queued';

    #[ORM\Column(name: 'subject_identifier', type: 'string', length: 190)]
    private string $subjectIdentifier;

    #[ORM\Column(name: 'target_reference', type: 'string', length: 240, nullable: true)]
    private ?string $targetReference = null;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_context', type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'started_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $operationKey, string $operationType, string $subjectIdentifier, ?string $targetReference = null, array $safeContext = [])
    {
        $this->operationKey = $operationKey;
        $this->operationType = $operationType;
        $this->subjectIdentifier = $subjectIdentifier;
        $this->targetReference = $targetReference;
        $this->safeContext = $safeContext;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function operationKey(): string
    {
        return $this->operationKey;
    }

    public function operationType(): string
    {
        return $this->operationType;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function subjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }

    public function targetReference(): ?string
    {
        return $this->targetReference;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function startedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function finishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function markRunning(): void
    {
        $this->status = 'running';
        $this->startedAt = new \DateTimeImmutable();
    }

    public function markSucceeded(): void
    {
        $this->status = 'succeeded';
        $this->finishedAt = new \DateTimeImmutable();
    }

    public function markFailed(string $safeReason): void
    {
        $this->status = 'failed';
        $this->safeContext['failure_reason'] = $safeReason;
        $this->finishedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getOperationKey(): string
    {
        return $this->operationKey();
    }

    public function getOperationType(): string
    {
        return $this->operationType();
    }

    public function getStatus(): string
    {
        return $this->status();
    }

    public function getSubjectIdentifier(): string
    {
        return $this->subjectIdentifier();
    }

    public function getTargetReference(): ?string
    {
        return $this->targetReference();
    }

    /** @return array<string, mixed> */
    public function getSafeContext(): array
    {
        return $this->safeContext();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt();
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt();
    }
}
