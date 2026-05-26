<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Config;

use App\Administering\Value\Config\AdministrationConfigToolDescriptor;

interface AdministrationConfigToolServiceInterface
{
    public function descriptor(): AdministrationConfigToolDescriptor;

    public function loadData(): object;

    /**
     * @param array<string, mixed> $context
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, string>, file_changes:array<int, array<string, mixed>>, secret_changes:array<int, array<string, mixed>>}
     */
    public function save(object $data, array $context = []): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return array{status:string, messages:list<string>, masked_changes:array<string, string>, file_changes:array<int, array<string, mixed>>, secret_changes:array<int, array<string, mixed>>}
     */
    public function apply(object $data, array $context = []): array;
}
