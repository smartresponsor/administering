<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Persisted metadata-only record of a controlled authorization apply attempt.
 *
 * The actual authorization execution remains owned by the host component. Administering stores only
 * the safe operator trace and result metadata.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_acl_mutation_apply_record')]
#[ORM\Index(name: 'idx_administration_acl_apply_request_key', columns: ['request_key'])]
#[ORM\Index(name: 'idx_administration_acl_apply_status', columns: ['status'])]
final class AdministrationAclMutationApplyRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'request_key', type: 'string', length: 180)]
    private string $requestKey;

    #[ORM\Column(name: 'mutation_type', type: 'string', length: 80)]
    private string $mutationType;

    #[ORM\Column(name: 'subject_identifier', type: 'string', length: 180)]
    private string $subjectIdentifier;

    #[ORM\Column(name: 'permission_or_role_key', type: 'string', length: 180)]
    private string $permissionOrRoleKey;

    #[ORM\Column(name: 'scope_key', type: 'string', length: 180)]
    private string $scopeKey;

    #[ORM\Column(name: 'requested_by_subject', type: 'string', length: 180)]
    private string $requestedBySubject;

    #[ORM\Column(name: 'status', type: 'string', length: 40)]
    private string $status;

    #[ORM\Column(name: 'succeeded', type: 'boolean')]
    private bool $succeeded;

    #[ORM\Column(name: 'safe_message', type: 'string', length: 500)]
    private string $safeMessage;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_result_payload', type: Types::JSON)]
    private array $safeResultPayload = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $safeResultPayload */
    public function __construct(
        string $requestKey,
        string $mutationType,
        string $subjectIdentifier,
        string $permissionOrRoleKey,
        string $scopeKey,
        string $requestedBySubject,
        string $status,
        bool $succeeded,
        string $safeMessage,
        array $safeResultPayload = [],
    ) {
        $this->requestKey = $requestKey;
        $this->mutationType = $mutationType;
        $this->subjectIdentifier = $subjectIdentifier;
        $this->permissionOrRoleKey = $permissionOrRoleKey;
        $this->scopeKey = $scopeKey;
        $this->requestedBySubject = $requestedBySubject;
        $this->status = $status;
        $this->succeeded = $succeeded;
        $this->safeMessage = $safeMessage;
        $this->safeResultPayload = $safeResultPayload;
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

    public function mutationType(): string
    {
        return $this->mutationType;
    }

    public function subjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }

    public function permissionOrRoleKey(): string
    {
        return $this->permissionOrRoleKey;
    }

    public function scopeKey(): string
    {
        return $this->scopeKey;
    }

    public function requestedBySubject(): string
    {
        return $this->requestedBySubject;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function succeeded(): bool
    {
        return $this->succeeded;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    /** @return array<string, mixed> */
    public function safeResultPayload(): array
    {
        return $this->safeResultPayload;
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

    public function getMutationType(): string
    {
        return $this->mutationType();
    }

    public function getSubjectIdentifier(): string
    {
        return $this->subjectIdentifier();
    }

    public function getPermissionOrRoleKey(): string
    {
        return $this->permissionOrRoleKey();
    }

    public function getScopeKey(): string
    {
        return $this->scopeKey();
    }

    public function getRequestedBySubject(): string
    {
        return $this->requestedBySubject();
    }

    public function getStatus(): string
    {
        return $this->status();
    }

    public function isSucceeded(): bool
    {
        return $this->succeeded();
    }

    public function getSafeMessage(): string
    {
        return $this->safeMessage();
    }

    /** @return array<string, mixed> */
    public function getSafeResultPayload(): array
    {
        return $this->safeResultPayload();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }
}
