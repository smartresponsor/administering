<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Owner-reviewable issue found in a non-destructive service-tool relocation plan.
 *
 * The issue is diagnostic only. It does not approve an automatic move, deletion,
 * namespace rewrite, or service alias rewrite.
 */
final readonly class AdministrationServiceToolRelocationPlanIssue
{
    public function __construct(
        public string $severity,
        public string $code,
        public string $message,
        public ?string $sourcePath,
        public ?string $targetPath,
        public ?string $section,
    ) {
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'sourcePath' => $this->sourcePath,
            'targetPath' => $this->targetPath,
            'section' => $this->section,
        ];
    }
}
