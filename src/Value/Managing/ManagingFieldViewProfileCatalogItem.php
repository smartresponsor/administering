<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfileCatalogItem
{
    /** @param list<string> $ruleKeys */
    public function __construct(
        public string $profileKey,
        public string $label,
        public string $owner,
        public string $scope,
        public bool $userEditable,
        public array $ruleKeys = [],
        public ?string $description = null,
    ) {
    }
}
