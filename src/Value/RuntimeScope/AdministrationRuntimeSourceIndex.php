<?php

declare(strict_types=1);

namespace App\Administering\Value\RuntimeScope;

final readonly class AdministrationRuntimeSourceIndex
{
    /**
     * @param list<array<string, mixed>> $summaryItems
     * @param list<array<string, mixed>> $sections
     * @param list<string>               $errors
     */
    public function __construct(
        public string $title,
        public string $description,
        public array $summaryItems,
        public array $sections,
        public array $errors = [],
    ) {
    }
}
