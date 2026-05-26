<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

use App\Administering\Value\Operation\AdministrationOperationType;

/**
 * Owner-side configuration tool definition exposed to Administering.
 *
 * Owner repositories keep their own prefix, forms, data objects, and handlers.
 * Administering consumes this definition only as a projection source for the
 * SQLite/EasyAdmin index; it must not become the owner of external tool meaning.
 */
final readonly class AdministrationOwnerConfigurationToolDefinition
{
    public function __construct(
        public string $componentKey,
        public string $componentToken,
        public string $toolSlug,
        public string $serviceClass,
        public string $serviceShortName,
        public string $label,
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

    public function toolKey(): string
    {
        return strtolower($this->componentToken).'.'.$this->camelToSnake($this->toolSlug);
    }

    public function expectedServicePrefix(): string
    {
        return $this->normalizedComponentKey().'Configuration';
    }

    public function isOwnerSidePrefixed(): bool
    {
        return str_starts_with($this->serviceShortName, $this->expectedServicePrefix())
            && str_ends_with($this->serviceShortName, 'Service');
    }

    public function resolvedChecksum(): string
    {
        if (null !== $this->checksum && '' !== $this->checksum) {
            return $this->checksum;
        }

        return hash('sha256', $this->componentToken.'|'.$this->toolSlug.'|'.$this->serviceClass.'|'.($this->formTypeClass ?? ''));
    }

    public function resolvedServiceFile(): string
    {
        return $this->serviceFile ?? 'owner://'.strtolower($this->componentToken).'/'.$this->serviceShortName.'.php';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'componentKey' => $this->componentKey,
            'componentToken' => $this->componentToken,
            'toolSlug' => $this->toolSlug,
            'toolKey' => $this->toolKey(),
            'label' => $this->label,
            'serviceClass' => $this->serviceClass,
            'serviceShortName' => $this->serviceShortName,
            'serviceFile' => $this->resolvedServiceFile(),
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
        ];
    }

    private function normalizedComponentKey(): string
    {
        $componentKey = trim($this->componentKey);

        return '' === $componentKey ? ucfirst(strtolower($this->componentToken)) : ucfirst($componentKey);
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
}
