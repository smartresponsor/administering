<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

final readonly class AdministrationRollingSubjectAccessReport
{
    public function __construct(private string $subjectIdentifier, private string $scope)
    {
    }

    public function subjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    /** @return list<array<string, mixed>> */
    public function assignedRoles(): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function directRules(): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function rolePermissions(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'subject_identifier' => $this->subjectIdentifier,
            'scope' => $this->scope,
            'assigned_roles' => [],
            'direct_rules' => [],
            'role_permissions' => [],
            'mode' => 'administering_self_contained_dry_runtime',
        ];
    }
}
