<?php

declare(strict_types=1);

namespace App\Administering\Value\Accessing;

/**
 * Safe account row displayed by Administering.
 *
 * This projection is intentionally limited to identity/status metadata. Password
 * hashes, reset tokens, TOTP secrets, recovery codes, and raw session payloads
 * must remain owned by Accessing and must never be copied into Administering.
 */
final class AdministrationAccountProjection
{
    /** @param list<string> $bootstrapRoles */
    public function __construct(
        private readonly string $subjectId,
        private readonly string $identifier,
        private readonly bool $active,
        private readonly bool $verified,
        private readonly array $bootstrapRoles = [],
        private readonly ?string $displayName = null,
    ) {
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function verified(): bool
    {
        return $this->verified;
    }

    /** @return list<string> */
    public function bootstrapRoles(): array
    {
        return $this->bootstrapRoles;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }
}
