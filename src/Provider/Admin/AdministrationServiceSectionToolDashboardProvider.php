<?php

declare(strict_types=1);

namespace App\Administering\Provider\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceSectionCatalogInterface;
use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\ProviderInterface\Admin\AdministrationServiceSectionToolDashboardProviderInterface;
use App\Administering\Value\Admin\AdministrationServiceSection;
use App\Administering\Value\Admin\AdministrationServiceSectionToolDashboard;
use App\Administering\Value\Admin\AdministrationServiceToolDetail;

/**
 * Builds EasyAdmin dashboard data for a canonical service section.
 */
final readonly class AdministrationServiceSectionToolDashboardProvider implements AdministrationServiceSectionToolDashboardProviderInterface
{
    public function __construct(
        private AdministrationServiceSectionCatalogInterface $sectionCatalog,
        private AdministrationServiceToolCatalogInterface $toolCatalog,
    ) {
    }

    public function dashboardForSection(string $sectionKey): ?AdministrationServiceSectionToolDashboard
    {
        $section = $this->section($sectionKey);
        if (null === $section) {
            return null;
        }

        return new AdministrationServiceSectionToolDashboard(
            section: $section,
            tools: $this->toolCatalog->toolsForSection($section->key),
        );
    }

    public function detailForTool(string $sectionKey, string $toolShortName): ?AdministrationServiceToolDetail
    {
        $section = $this->section($sectionKey);
        if (null === $section) {
            return null;
        }

        foreach ($this->toolCatalog->toolsForSection($section->key) as $tool) {
            if ($tool->shortName === $toolShortName) {
                return new AdministrationServiceToolDetail($section, $tool);
            }
        }

        return null;
    }

    private function section(string $sectionKey): ?AdministrationServiceSection
    {
        foreach ($this->sectionCatalog->sections() as $section) {
            if ($section->key === $sectionKey) {
                return $section;
            }
        }

        return null;
    }
}
