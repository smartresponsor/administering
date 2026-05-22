<?php

declare(strict_types=1);

namespace App\Administering\Value\Rolling;

/**
 * Explains one read-only priority layer in the Managing field view profile hierarchy.
 */
final readonly class AdministrationFieldViewProfilePriorityRow
{
    public function __construct(
        public int $priority,
        public string $layer,
        public string $ownerComponent,
        public string $decisionType,
        public string $canOverride,
        public string $notes,
    ) {
    }
}
