<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes one service tool detail page rendered inside the EasyAdmin layout.
 */
final readonly class AdministrationServiceToolDetail
{
    public function __construct(
        public AdministrationServiceSection $section,
        public AdministrationServiceTool $tool,
    ) {
    }
}
