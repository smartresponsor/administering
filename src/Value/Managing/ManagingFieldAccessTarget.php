<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldAccessTarget
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        public string $componentKey,
        public string $resourceClass,
        public string $fieldName,
        public string $pageName,
        public string $operation = 'view',
        public array $attributes = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'component_key' => $this->componentKey,
            'resource_class' => $this->resourceClass,
            'field_name' => $this->fieldName,
            'page_name' => $this->pageName,
            'operation' => $this->operation,
            'attributes' => $this->attributes,
        ];
    }
}
