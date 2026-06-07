<?php

declare(strict_types=1);

namespace App\Administering\Value\Accessing;

/**
 * Administering-owned descriptor for safe account action request surfaces.
 */
final readonly class AdministrationAccountActionDescriptor
{
    public function __construct(
        public string $key,
        public string $label,
        public string $riskLevel,
        public bool $requiresReason = true,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function riskLevel(): string
    {
        return $this->riskLevel;
    }

    public function getRiskLevel(): string
    {
        return $this->riskLevel;
    }

    public function requiresReason(): bool
    {
        return $this->requiresReason;
    }

    public function isRequiresReason(): bool
    {
        return $this->requiresReason;
    }
}
