<?php

declare(strict_types=1);

namespace App\Administering\Value\Tool;

use App\Administering\Value\Operation\AdministrationOperationType;

final readonly class ConfigurationToolDefinition
{
    /**
     * @param list<string>         $tags
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public string $serviceClass,
        public string $operation,
        public array $tags = [],
        public array $metadata = [],
        public ?string $componentKey = null,
        public ?string $componentToken = null,
        public ?string $toolSlug = null,
        public ?string $serviceShortName = null,
        public ?string $formTypeClass = null,
        public ?string $formDataClass = null,
        public bool $executable = false,
        public string $kind = 'owner_configuration_tool',
        public string $operationType = AdministrationOperationType::SERVICE_TOOL_LAUNCH,
        public ?string $serviceFile = null,
        public ?string $checksum = null,
        public ?string $primaryRouteName = null,
        public ?string $primaryRouteLabel = null,
    ) {
    }

    public function componentKey(): string
    {
        return $this->componentKey ?? $this->metadataString('componentKey', 'Administering');
    }

    public function componentToken(): string
    {
        return $this->componentToken ?? $this->metadataString('componentToken', strtolower($this->componentKey()));
    }

    public function toolSlug(): string
    {
        return $this->toolSlug ?? $this->metadataString('toolSlug', $this->key);
    }

    public function serviceShortName(): string
    {
        if (null !== $this->serviceShortName && '' !== $this->serviceShortName) {
            return $this->serviceShortName;
        }

        $parts = explode('\\', $this->serviceClass);
        $shortName = end($parts);

        return '' !== $shortName ? $shortName : $this->serviceClass;
    }

    public function toolKey(): string
    {
        return strtolower($this->componentToken()).'.'.$this->camelToSnake($this->toolSlug());
    }

    public function expectedServicePrefix(): string
    {
        return $this->normalizedComponentKey().'Configuration';
    }

    public function isOwnerSidePrefixed(): bool
    {
        return str_starts_with($this->serviceShortName(), $this->expectedServicePrefix())
            && str_ends_with($this->serviceShortName(), 'Service');
    }

    public function resolvedChecksum(): string
    {
        if (null !== $this->checksum && '' !== $this->checksum) {
            return $this->checksum;
        }

        return hash('sha256', $this->componentToken().'|'.$this->toolSlug().'|'.$this->serviceClass.'|'.($this->formTypeClass ?? ''));
    }

    public function resolvedServiceFile(): string
    {
        if (null !== $this->serviceFile && '' !== $this->serviceFile) {
            return $this->serviceFile;
        }

        return 'owner://'.strtolower($this->componentToken()).'/'.$this->serviceShortName().'.php';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'componentKey' => $this->componentKey(),
            'componentToken' => $this->componentToken(),
            'toolSlug' => $this->toolSlug(),
            'toolKey' => $this->toolKey(),
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'serviceClass' => $this->serviceClass,
            'serviceShortName' => $this->serviceShortName(),
            'serviceFile' => $this->resolvedServiceFile(),
            'operation' => $this->operation,
            'formTypeClass' => $this->formTypeClass,
            'formDataClass' => $this->formDataClass,
            'executable' => $this->executable,
            'kind' => $this->kind,
            'operationType' => $this->operationType,
            'checksum' => $this->resolvedChecksum(),
            'primaryRouteName' => $this->primaryRouteName,
            'primaryRouteLabel' => $this->primaryRouteLabel,
            'expectedServicePrefix' => $this->expectedServicePrefix(),
            'ownerSidePrefixed' => $this->isOwnerSidePrefixed(),
            'tags' => $this->tags,
            'metadata' => $this->metadata,
        ];
    }

    private function normalizedComponentKey(): string
    {
        $componentKey = trim($this->componentKey());

        return '' === $componentKey ? ucfirst(strtolower($this->componentToken())) : ucfirst($componentKey);
    }

    private function camelToSnake(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            return 'unknown';
        }

        $snake = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value);

        return strtolower($snake);
    }

    private function metadataString(string $key, string $fallback): string
    {
        $value = $this->metadata[$key] ?? null;

        return is_string($value) && '' !== trim($value) ? trim($value) : $fallback;
    }
}
