<?php

declare(strict_types=1);

namespace App\Administering\Value;

final class AdministrationCurrentUserContext
{
    /** @param list<string> $bootstrapRoles */
    public function __construct(
        private readonly string $subjectIdentifier,
        private readonly string $userIdentifier,
        private readonly array $bootstrapRoles = [],
    ) {
    }

    public function subjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }

    public function userIdentifier(): string
    {
        return $this->userIdentifier;
    }

    /** @return list<string> */
    public function bootstrapRoles(): array
    {
        return $this->bootstrapRoles;
    }
}
