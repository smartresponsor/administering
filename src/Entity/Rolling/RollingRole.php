<?php

declare(strict_types=1);

namespace App\Administering\Entity\Rolling;

class RollingRole
{
    private bool $enabled = false;
    private string $roleKey = '';

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getRoleKey(): string
    {
        return $this->roleKey;
    }
}
