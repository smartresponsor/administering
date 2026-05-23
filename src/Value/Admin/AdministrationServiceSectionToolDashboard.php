<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Describes the EasyAdmin tool dashboard for one service section.
 */
final readonly class AdministrationServiceSectionToolDashboard
{
    /**
     * @param list<AdministrationServiceTool> $tools
     */
    public function __construct(
        public AdministrationServiceSection $section,
        public array $tools,
    ) {
    }

    public function toolCount(): int
    {
        return count($this->tools);
    }
}
