<?php

declare(strict_types=1);

namespace App\Administering\Entity\Rolling;

class RollingRoleHierarchy
{
    private bool $enabled = false;
    private string $parentRoleKey = '';
    private string $childRoleKey = '';

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function parentRoleKey(): string
    {
        return $this->parentRoleKey;
    }

    public function childRoleKey(): string
    {
        return $this->childRoleKey;
    }
}
