<?php

declare(strict_types=1);

namespace App\Administering\Entity\Rolling;

class RollingSubjectRoleAssignment
{
    private string $subjectIdentifier = '';
    private string $roleKey = '';

    public function getSubjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }

    public function getRoleKey(): string
    {
        return $this->roleKey;
    }
}
