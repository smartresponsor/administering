<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Config;

use App\Administering\Value\Config\ConfigToolDescriptor;

interface ConfigToolServiceInterface
{
    public function descriptor(): ConfigToolDescriptor;

    public function loadData(): object;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function save(object $data, array $context = []): array;

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function apply(object $data, array $context = []): array;
}
