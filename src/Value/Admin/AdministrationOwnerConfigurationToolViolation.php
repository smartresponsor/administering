<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Validation issue for an owner-provided configuration tool definition.
 */
final readonly class AdministrationOwnerConfigurationToolViolation
{
    public function __construct(
        public string $severity,
        public string $componentKey,
        public string $componentToken,
        public string $toolKey,
        public string $field,
        public string $message,
        public ?string $expected = null,
        public ?string $actual = null,
    ) {
    }

    public function isError(): bool
    {
        return 'error' === $this->severity;
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'componentKey' => $this->componentKey,
            'componentToken' => $this->componentToken,
            'toolKey' => $this->toolKey,
            'field' => $this->field,
            'message' => $this->message,
            'expected' => $this->expected,
            'actual' => $this->actual,
        ];
    }
}
