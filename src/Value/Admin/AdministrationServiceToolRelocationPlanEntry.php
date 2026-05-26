<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Non-destructive relocation recommendation for one non-tool class currently under src/Service.
 *
 * The entry is a planning artifact only. It does not imply that the class can be
 * moved without reviewing constructor wiring, aliases, imports, routes, and tests.
 */
final readonly class AdministrationServiceToolRelocationPlanEntry
{
    public function __construct(
        public string $section,
        public string $sourcePath,
        public string $shortName,
        public string $reason,
        public string $suggestedAction,
        public ?string $targetPath,
        public ?string $targetNamespace,
        public bool $automaticMoveAllowed,
        public string $reviewNote,
    ) {
    }

    /** @return array<string, bool|string|null> */
    public function toArray(): array
    {
        return [
            'section' => $this->section,
            'sourcePath' => $this->sourcePath,
            'shortName' => $this->shortName,
            'reason' => $this->reason,
            'suggestedAction' => $this->suggestedAction,
            'targetPath' => $this->targetPath,
            'targetNamespace' => $this->targetNamespace,
            'automaticMoveAllowed' => $this->automaticMoveAllowed,
            'reviewNote' => $this->reviewNote,
        ];
    }
}
