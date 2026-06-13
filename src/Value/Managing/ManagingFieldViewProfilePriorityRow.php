<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldViewProfilePriorityRow
{
    public function __construct(
        public int $priority,
        public string $nameEntity,
        public string $owner,
        public string $decision,
        public string $effect,
        public string $description,
    ) {
    }
}
