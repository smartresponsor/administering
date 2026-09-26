<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class AdministrationManagingFieldAccessCatalogItem
{
    /** @param list<string> $scopes */
    public function __construct(
        public string $permissionKey,
        public string $label,
        public string $category,
        public string $controlPlaneGroup,
        public array $scopes,
        public bool $sensitive,
        public bool $registeredInRolling,
    ) {
    }
}
