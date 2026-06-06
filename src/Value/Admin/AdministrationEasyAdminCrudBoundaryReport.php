<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

final class AdministrationEasyAdminCrudBoundaryReport
{
    /**
     * @param list<array{controller: string, entity: string|null, table: string|null, nativeCrudTemplate: bool, sqliteSystemTable: bool, symfonyFormSafe: bool, messages: list<string>}> $items
     * @param list<string>                                                                                                                                                               $errors
     * @param list<string>                                                                                                                                                               $warnings
     */
    public function __construct(
        private readonly array $items,
        private readonly array $errors,
        private readonly array $warnings,
    ) {
    }

    /**
     * @return list<array{controller: string, entity: string|null, table: string|null, nativeCrudTemplate: bool, sqliteSystemTable: bool, symfonyFormSafe: bool, messages: list<string>}>
     */
    public function items(): array
    {
        return $this->items;
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
    }

    /** @return array{items: list<array{controller: string, entity: string|null, table: string|null, nativeCrudTemplate: bool, sqliteSystemTable: bool, symfonyFormSafe: bool, messages: list<string>}>, errors: list<string>, warnings: list<string>} */
    public function toArray(): array
    {
        return [
            'items' => $this->items,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
