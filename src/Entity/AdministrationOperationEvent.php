<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use App\Administering\Repository\AdministrationOperationEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Metadata-only event log for Administering operation status transitions.
 *
 * Operation events must never contain secrets, raw .env values, decrypted credentials,
 * source file dumps, session payloads, or password/2FA internals.
 */
#[ORM\Entity(repositoryClass: AdministrationOperationEventRepository::class)]
#[ORM\Table(name: 'administration_operation_event')]
#[ORM\Index(name: 'idx_administration_operation_event_run', columns: ['operation_key'])]
#[ORM\Index(name: 'idx_administration_operation_event_status', columns: ['status'])]
final class AdministrationOperationEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'operation_key', type: 'string', length: 180)]
    private string $operationKey;

    #[ORM\Column(name: 'status', type: 'string', length: 40)]
    private string $status;

    #[ORM\Column(name: 'safe_message', type: 'string', length: 500)]
    private string $safeMessage;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_context', type: Types::JSON)]
    private array $safeContext = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $safeContext */
    public function __construct(string $operationKey, string $status, string $safeMessage, array $safeContext = [], ?int $id = null)
    {
        $this->id = $id;
        $this->operationKey = $operationKey;
        $this->status = $status;
        $this->safeMessage = $safeMessage;
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

    public function status(): string
    {
        return $this->status;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
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

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getOperationKey(): string
    {
        return $this->operationKey();
    }

    public function getStatus(): string
    {
        return $this->status();
    }

    public function getSafeMessage(): string
    {
        return $this->safeMessage();
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
}
