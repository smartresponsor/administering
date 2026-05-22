<?php

declare(strict_types=1);

namespace App\Administering\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Persisted metadata-only record of a Rolling ACL mutation dry-run review.
 *
 * This record belongs to Administering system storage. It must not contain raw
 * policy internals, secrets, session payloads, passwords, or decrypted values.
 */
#[ORM\Entity]
#[ORM\Table(name: 'administration_acl_mutation_review_record')]
#[ORM\Index(name: 'idx_administration_acl_review_request_key', columns: ['request_key'])]
#[ORM\Index(name: 'idx_administration_acl_review_subject', columns: ['subject_identifier'])]
#[ORM\Index(name: 'idx_administration_acl_review_permission', columns: ['permission_or_role_key'])]
final class AdministrationAclMutationReviewRecord
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

    #[ORM\Column(name: 'valid', type: 'boolean')]
    private bool $valid;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'safe_review_payload', type: Types::JSON)]
    private array $safeReviewPayload = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @param array<string, mixed> $safeReviewPayload */
    public function __construct(
        string $requestKey,
        string $mutationType,
        string $subjectIdentifier,
        string $permissionOrRoleKey,
        string $scopeKey,
        string $requestedBySubject,
        bool $valid,
        array $safeReviewPayload = [],
    ) {
        $this->requestKey = $requestKey;
        $this->mutationType = $mutationType;
        $this->subjectIdentifier = $subjectIdentifier;
        $this->permissionOrRoleKey = $permissionOrRoleKey;
        $this->scopeKey = $scopeKey;
        $this->requestedBySubject = $requestedBySubject;
        $this->valid = $valid;
        $this->safeReviewPayload = $safeReviewPayload;
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

    public function valid(): bool
    {
        return $this->valid;
    }

    /** @return array<string, mixed> */
    public function safeReviewPayload(): array
    {
        return $this->safeReviewPayload;
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

    public function isValid(): bool
    {
        return $this->valid();
    }

    /** @return array<string, mixed> */
    public function getSafeReviewPayload(): array
    {
        return $this->safeReviewPayload();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt();
    }
}
