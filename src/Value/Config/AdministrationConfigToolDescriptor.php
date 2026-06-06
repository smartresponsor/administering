<?php

declare(strict_types=1);

namespace App\Administering\Value\Config;

final readonly class AdministrationConfigToolDescriptor
{
    /**
     * @param list<string> $secretNames
     */
    /**
     * @param list<string>         $readableFiles
     * @param list<string>         $writableFiles
     * @param list<string>         $editableFields
     * @param list<string>         $sensitiveFields
     * @param list<string>         $secretNames
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $applicationCode,
        public string $toolCode,
        public string $label,
        public string $description,
        public string $formClass,
        public string $serviceClass,
        public string $requiredPermission,
        public array $editableFields = [],
        public array $sensitiveFields = [],
        public array $readableFiles = [],
        public array $writableFiles = [],
        public array $metadata = [],
        public array $secretNames = [],
        public string $applyStrategy = 'component_yaml',
    ) {
    }
}
