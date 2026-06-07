<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

final readonly class AdministrationRollingPermissionDescriptor
{
    /** @param list<string> $scopes */
    public function __construct(
        private string $key,
        private string $label,
        private string $category,
        private array $scopes = [],
        private bool $sensitive = false,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function category(): string
    {
        return $this->category;
    }

    /** @return list<string> */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function sensitive(): bool
    {
        return $this->sensitive;
    }
}
