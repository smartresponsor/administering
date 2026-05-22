<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Administering-local view of a Rolling permission descriptor.
 */
final class AdministrationPermissionDescriptor
{
    /** @param list<string> $scopes */
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly string $category,
        private readonly array $scopes = ['global'],
        private readonly bool $sensitive = false,
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
