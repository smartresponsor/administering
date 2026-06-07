<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

final readonly class AdministrationRollingFieldAccessDecisionRequest
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        public string $permissionKey,
        public string $componentKey,
        public string $resourceClass,
        public string $fieldName,
        public string $pageName,
        public string $operation,
        public string $subjectIdentifier,
        public array $attributes = [],
    ) {
    }
}
