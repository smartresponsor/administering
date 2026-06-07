<?php

declare(strict_types=1);

namespace App\Administering\Value\Managing;

final readonly class ManagingFieldVisibilityExplanationStep
{
    public const AXIS_AVAILABILITY = 'availability';
    public const AXIS_ACCESS = 'access';
    public const AXIS_PRESENTATION = 'presentation';

    public function __construct(
        public int $priority,
        public string $label,
        public string $owner,
        public string $axis,
        public string $input,
        public string $output,
        public string $description,
    ) {
    }
}
