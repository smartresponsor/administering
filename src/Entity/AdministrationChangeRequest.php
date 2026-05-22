<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a controlled administrative change proposal.
 *
 * Git-owned files, composer changes, generated patches, and secret actions should be
 * routed through change requests instead of silent direct writes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_change_request')]
class AdministrationChangeRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'request_key', type: 'string', length: 160, unique: true)]
    private string $requestKey;

    #[ORM\Column(name: 'change_type', type: 'string', length: 80)]
    private string $changeType;

    #[ORM\Column(name: 'target_reference', type: 'string', length: 240)]
    private string $targetReference;

    #[ORM\Column(name: 'status', type: 'string', length: 40)]
    private string $status = 'draft';

    #[ORM\Column(name: 'payload', type: 'json')]
    private array $payload = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $requestKey, string $changeType, string $targetReference, array $payload = [])
    {
        $this->requestKey = $requestKey;
        $this->changeType = $changeType;
        $this->targetReference = $targetReference;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requestKey(): string
    {
        return $this->requestKey;
    }

    public function changeType(): string
    {
        return $this->changeType;
    }

    public function targetReference(): string
    {
        return $this->targetReference;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function markProposed(): void
    {
        $this->status = 'proposed';
    }

    public function markApplied(): void
    {
        $this->status = 'applied';
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return $this->payload;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id();
    }

    public function getRequestKey(): string
    {
        return $this->requestKey();
    }

    public function getChangeType(): string
    {
        return $this->changeType();
    }

    public function getTargetReference(): string
    {
        return $this->targetReference();
    }

    public function getStatus(): string
    {
        return $this->status();
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }
}
