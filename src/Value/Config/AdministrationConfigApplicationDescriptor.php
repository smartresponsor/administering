<?php

declare(strict_types=1);

namespace App\Administering\Value\Config;

final readonly class AdministrationConfigApplicationDescriptor
{
    public function __construct(
        public string $applicationCode,
        public string $label,
        public string $rootPath,
        public string $manifestPath,
        public string $checksum,
        public bool $enabled = true,
        public array $metadata = [],
    ) {
    }
}
