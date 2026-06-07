<?php

declare(strict_types=1);

namespace App\Administering\Value\Config;

final readonly class ConfigVariable
{
    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $constraints
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type = ConfigVariableType::STRING,
        public string $storage = ConfigVariableStorage::ENV,
        public mixed $defaultValue = null,
        public bool $required = false,
        public array $options = [],
        public ?string $help = null,
        public array $constraints = [],
        public array $metadata = [],
        public ?string $targetFile = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'storage' => $this->storage,
            'defaultValue' => $this->defaultValue,
            'required' => $this->required,
            'options' => $this->options,
            'help' => $this->help,
            'constraints' => $this->constraints,
            'metadata' => $this->metadata,
            'targetFile' => $this->targetFile,
        ];
    }
}
