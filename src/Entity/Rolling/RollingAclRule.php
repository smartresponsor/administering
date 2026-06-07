<?php

declare(strict_types=1);

namespace App\Administering\Entity\Rolling;

class RollingAclRule
{
    private bool $enabled = false;
    private string $effect = 'deny';
    private string $permissionKey = '';
    private string $subjectIdentifier = '';

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

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

    public function getSubjectIdentifier(): string
    {
        return $this->subjectIdentifier;
    }
}
