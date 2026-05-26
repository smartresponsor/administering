<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Safe invocation DTO passed from a persisted service-tool operation run to a
 * concrete tool handler.
 *
 * The payload intentionally contains only metadata copied from the tool record
 * and public/scalar form data redacted by AdministrationOperationPlan.
 */
final readonly class AdministrationServiceToolInvocation
{
    /**
     * @param array<string, mixed> $formData
     * @param array<string, mixed> $safeContext
     */
    public function __construct(
        public string $operationKey,
        public string $toolKey,
        public string $sectionKey,
        public string $toolSlug,
        public string $serviceClass,
        public ?string $serviceFile,
        public ?string $formTypeClass,
        public ?string $formDataClass,
        public bool $executable,
        public string $sourceOwnership,
        public ?string $ownerComponentKey,
        public ?string $ownerComponentToken,
        public ?string $ownerProviderClass,
        public ?string $ownerServiceClass,
        public ?string $ownerSourceLabel,
        public array $formData,
        public array $safeContext,
    ) {
    }

    /** @param array<string, mixed> $safeContext */
    public static function fromSafeContext(string $operationKey, array $safeContext): self
    {
        $formData = $safeContext['formData'] ?? [];

        return new self(
            operationKey: $operationKey,
            toolKey: self::requiredString($safeContext, 'toolKey'),
            sectionKey: self::requiredString($safeContext, 'sectionKey'),
            toolSlug: self::requiredString($safeContext, 'toolSlug'),
            serviceClass: self::requiredString($safeContext, 'serviceClass'),
            serviceFile: self::optionalString($safeContext, 'serviceFile'),
            formTypeClass: self::optionalString($safeContext, 'formTypeClass'),
            formDataClass: self::optionalString($safeContext, 'formDataClass'),
            executable: self::boolValue($safeContext, 'executable'),
            sourceOwnership: self::stringValue($safeContext, 'sourceOwnership', 'administering_internal'),
            ownerComponentKey: self::optionalString($safeContext, 'ownerComponentKey'),
            ownerComponentToken: self::optionalString($safeContext, 'ownerComponentToken'),
            ownerProviderClass: self::optionalString($safeContext, 'ownerProviderClass'),
            ownerServiceClass: self::optionalString($safeContext, 'ownerServiceClass'),
            ownerSourceLabel: self::optionalString($safeContext, 'ownerSourceLabel'),
            formData: is_array($formData) ? $formData : [],
            safeContext: $safeContext,
        );
    }

    public function stringFormValue(string $key, string $default = ''): string
    {
        $value = $this->formData[$key] ?? null;

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** @return list<string> */
    public function stringListFormValue(string $key): array
    {
        $value = $this->formData[$key] ?? null;
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static fn (mixed $item): string => trim((string) $item), $value)));
    }

    public function formDataClass(): ?string
    {
        $value = $this->formData['_data_class'] ?? null;

        return is_string($value) && '' !== trim($value) ? $value : null;
    }

    /** @param array<string, mixed> $safeContext */
    private static function requiredString(array $safeContext, string $key): string
    {
        $value = $safeContext[$key] ?? null;
        if (!is_string($value) || '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('Service tool invocation context is missing required string "%s".', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $safeContext */
    private static function optionalString(array $safeContext, string $key): ?string
    {
        $value = $safeContext[$key] ?? null;

        return is_string($value) && '' !== trim($value) ? $value : null;
    }

    /** @param array<string, mixed> $safeContext */
    private static function stringValue(array $safeContext, string $key, string $default): string
    {
        $value = $safeContext[$key] ?? null;

        return is_string($value) && '' !== trim($value) ? $value : $default;
    }

    /** @param array<string, mixed> $safeContext */
    private static function boolValue(array $safeContext, string $key): bool
    {
        $value = $safeContext[$key] ?? null;

        return true === $value || 1 === $value || '1' === $value || 'true' === $value;
    }
}
