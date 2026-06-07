<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Local Administering-owned ACL mutation review request.
 *
 * This value intentionally does not depend on Rolling classes. Administering can
 * collect and persist operator review metadata even when Rolling is not installed
 * in the local dry-runtime container.
 */
final readonly class AdministrationRollingAclMutationRequest
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $mutationType,
        private string $subjectIdentifier,
        private string $permissionOrRoleKey,
        private string $scopeKey,
        private string $requestedBySubject,
        private array $safeContext = [],
    ) {
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

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }
}
