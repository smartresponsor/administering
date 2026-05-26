<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes one src/Service/<Direction> file that cannot be treated as an Administering tool.
 *
 * The value is intentionally safe for console output and documentation because it
 * contains only local repository paths and convention diagnostics.
 */
final readonly class AdministrationServiceToolConventionViolation
{
    public function __construct(
        public string $section,
        public string $serviceFile,
        public string $shortName,
        public ?string $declaredNamespace,
        public string $expectedNamespace,
        public string $reason,
        public string $suggestedAction,
        public ?string $suggestedPath,
    ) {
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'section' => $this->section,
            'serviceFile' => $this->serviceFile,
            'shortName' => $this->shortName,
            'declaredNamespace' => $this->declaredNamespace,
            'expectedNamespace' => $this->expectedNamespace,
            'reason' => $this->reason,
            'suggestedAction' => $this->suggestedAction,
            'suggestedPath' => $this->suggestedPath,
        ];
    }
}
