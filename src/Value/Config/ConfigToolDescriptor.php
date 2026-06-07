<?php

declare(strict_types=1);

namespace App\Administering\Value\Config;

final readonly class ConfigToolDescriptor
{
    /**
     * @param list<ConfigVariable>  $variables
     * @param list<string>          $editableFields
     * @param list<string>          $sensitiveFields
     * @param list<string>          $readableFiles
     * @param list<string>          $writableFiles
     * @param array<string, mixed>  $metadata
     * @param array<string, string> $secretNames
     */
    public function __construct(
        public string $applicationCode,
        public string $toolCode,
        public string $label,
        public string $description = '',
        public array $variables = [],
        public array $metadata = [],
        public ?string $formClass = null,
        public ?string $serviceClass = null,
        public ?string $requiredPermission = null,
        public array $editableFields = [],
        public array $sensitiveFields = [],
        public array $readableFiles = [],
        public array $writableFiles = [],
        public array $secretNames = [],
        public string $applyStrategy = 'manual',
    ) {
    }
}
