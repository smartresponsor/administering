<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldAccessMatrixRow
{
    public function __construct(
        public int $priority,
        public string $nameEntity,
        public string $owner,
        public string $decision,
        public string $precedence,
        public string $description,
    ) {
    }
}
