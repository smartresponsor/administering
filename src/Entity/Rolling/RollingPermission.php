<?php

declare(strict_types=1);

namespace App\Administering\Entity\Rolling;

class RollingPermission
{
    private string $permissionKey = '';

    public function getPermissionKey(): string
    {
        return $this->permissionKey;
    }
}
