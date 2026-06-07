<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfileRuleShape
{
    /** @param list<string> $allowedValues */
    public function __construct(
        public string $key,
        public string $label,
        public string $valueType,
        public bool $required,
        public array $allowedValues = [],
        public ?string $description = null,
    ) {
    }
}
