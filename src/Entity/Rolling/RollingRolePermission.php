<?php

declare(strict_types=1);

namespace App\Administering\Entity\Rolling;

class RollingRolePermission
{
    private string $effect = 'deny';
    private string $permissionKey = '';
    private string $roleKey = '';

    public function getEffect(): string
    {
        return $this->effect;
    }

    public function setEffect(string $effect): void
    {
        $this->effect = $effect;
    }

    public function getPermissionKey(): string
    {
        return $this->permissionKey;
    }

    public function getRoleKey(): string
    {
        return $this->roleKey;
    }
}
