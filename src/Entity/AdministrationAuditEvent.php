<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'administration_audit_event')]
#[ORM\Index(name: 'idx_administration_audit_event_action', columns: ['action'])]
#[ORM\Index(name: 'idx_administration_audit_event_subject', columns: ['subject_identifier'])]
final class AdministrationAuditEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 190)]
    private string $action;

    #[ORM\Column(length: 190)]
    private string $subjectIdentifier;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON)]
    private array $context = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $context */
    public function __construct(string $action, string $subjectIdentifier, array $context = [], ?int $id = null)
    {
        $this->id = $id;
        $this->action = $action;
        $this->subjectIdentifier = $subjectIdentifier;
        $this->context = $context;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSubjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
