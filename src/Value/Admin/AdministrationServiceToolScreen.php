<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes the primary EasyAdmin-owned screen mapped to one service tool.
 *
 * The mapping is intentionally separated from filesystem service discovery so
 * src/Service/<Section> remains the tool catalog and screen routing stays in a
 * single canonical screen catalog.
 */
final readonly class AdministrationServiceToolScreen
{
    public function __construct(
        public string $routeName,
        public string $label,
    ) {
    }
}
