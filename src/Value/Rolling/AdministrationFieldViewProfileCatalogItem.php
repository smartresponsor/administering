<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Read-only control-plane descriptor for a Managing field view profile scope.
 */
final readonly class AdministrationFieldViewProfileCatalogItem
{
    /** @param list<string> $allowedOperations */
    public function __construct(
        public string $scopeType,
        public string $label,
        public string $ownerComponent,
        public string $storageOwner,
        public string $securityBoundary,
        public array $allowedOperations,
        public string $notes,
    ) {
    }

    public function profileScopedByUser(): bool
    {
        return 'user' === $this->scopeType;
    }
}
