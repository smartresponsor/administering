<?php

declare(strict_types=1);

namespace App\Administering\Provider\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceSectionCatalogInterface;
use App\Administering\Entity\AdministrationServiceSectionRecord;
use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ProviderInterface\Admin\AdministrationServiceToolMenuSectionProviderInterface;
use App\Administering\Value\Admin\AdministrationServiceSection;
use App\Administering\Value\Admin\AdministrationServiceToolMenuSection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Builds the left-menu section projection from SQLite materialized records.
 *
 * The filesystem/static catalog remains the metadata authority for icon and
 * permission. The SQLite projection is the runtime authority for tool counts and
 * readiness status after administering:service-tools:refresh-index.
 */
final readonly class AdministrationDoctrineServiceToolMenuSectionProvider implements AdministrationServiceToolMenuSectionProviderInterface
{
    public function __construct(
        private AdministrationServiceSectionCatalogInterface $sectionCatalog,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /** @return list<AdministrationServiceToolMenuSection> */
    public function menuSections(): array
    {
        $catalogSections = $this->catalogSectionsByKey();

        try {
            $manager = $this->entityManager();
            $sectionRecords = $this->sectionRecordsByKey($manager);
            $toolStats = $this->toolStatsBySection($manager);
        } catch (\Throwable) {
            return $this->fallbackSections($catalogSections);
        }

        if ([] === $sectionRecords && [] === $toolStats) {
            return $this->fallbackSections($catalogSections);
        }

        $knownKeys = array_values(array_unique(array_merge(
            array_keys($catalogSections),
            array_keys($sectionRecords),
            array_keys($toolStats),
        )));

        $catalogOrder = array_flip(array_keys($catalogSections));
        usort($knownKeys, static function (string $left, string $right) use ($catalogOrder): int {
            $leftPosition = $catalogOrder[$left] ?? PHP_INT_MAX;
            $rightPosition = $catalogOrder[$right] ?? PHP_INT_MAX;

            if ($leftPosition !== $rightPosition) {
                return $leftPosition <=> $rightPosition;
            }

            return $left <=> $right;
        });

        $sections = [];
        foreach ($knownKeys as $key) {
            $catalog = $catalogSections[$key] ?? null;
            $record = $sectionRecords[$key] ?? null;
            $stats = $toolStats[$key] ?? [
                'toolCount' => $record?->getToolCount() ?? 0,
                'executableCount' => 0,
                'formReadyCount' => 0,
                'indexedOnlyCount' => 0,
            ];

            $sections[] = new AdministrationServiceToolMenuSection(
                key: $key,
                label: $record?->getLabel() ?? $catalog->label ?? $this->labelFromKey($key),
                icon: $catalog->icon ?? 'fa fa-folder-tree',
                permission: $catalog->permission ?? 'administration.dashboard.view',
                toolCount: $stats['toolCount'],
                executableCount: $stats['executableCount'],
                formReadyCount: $stats['formReadyCount'],
                indexedOnlyCount: $stats['indexedOnlyCount'],
                status: $record?->getStatus() ?? 'runtime_tool_index',
                databaseBacked: null !== $record || array_key_exists($key, $toolStats),
            );
        }

        return $sections;
    }

    /** @return array<string, AdministrationServiceSection> */
    private function catalogSectionsByKey(): array
    {
        $sections = [];
        foreach ($this->sectionCatalog->sections() as $section) {
            $sections[$section->key] = $section;
        }

        return $sections;
    }

    /** @return array<string, AdministrationServiceSectionRecord> */
    private function sectionRecordsByKey(EntityManagerInterface $manager): array
    {
        /** @var list<AdministrationServiceSectionRecord> $records */
        $records = $manager->createQueryBuilder()
            ->select('record')
            ->from(AdministrationServiceSectionRecord::class, 'record')
            ->orderBy('record.sectionKey', 'ASC')
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($records as $record) {
            $indexed[$record->getSectionKey()] = $record;
        }

        return $indexed;
    }

    /** @return array<string, array{toolCount:int, executableCount:int, formReadyCount:int, indexedOnlyCount:int}> */
    private function toolStatsBySection(EntityManagerInterface $manager): array
    {
        /** @var list<AdministrationServiceToolRecord> $records */
        $records = $manager->createQueryBuilder()
            ->select('record')
            ->from(AdministrationServiceToolRecord::class, 'record')
            ->andWhere('record.visible = true')
            ->andWhere('record.enabled = true')
            ->orderBy('record.sectionKey', 'ASC')
            ->addOrderBy('record.position', 'ASC')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($records as $record) {
            $key = $record->getSectionKey();
            $stats[$key] ??= [
                'toolCount' => 0,
                'executableCount' => 0,
                'formReadyCount' => 0,
                'indexedOnlyCount' => 0,
            ];

            ++$stats[$key]['toolCount'];
            if ($record->isExecutable()) {
                ++$stats[$key]['executableCount'];
                continue;
            }

            if (null !== $record->getFormTypeClass()) {
                ++$stats[$key]['formReadyCount'];
                continue;
            }

            ++$stats[$key]['indexedOnlyCount'];
        }

        return $stats;
    }

    /** @param array<string, AdministrationServiceSection> $catalogSections */
    /** @return list<AdministrationServiceToolMenuSection> */
    /**
     * @param array<string, mixed> $catalogSections
     *
     * @return list<AdministrationServiceToolMenuSection>
     */
    private function fallbackSections(array $catalogSections): array
    {
        $sections = [];
        foreach ($catalogSections as $section) {
            $sections[] = new AdministrationServiceToolMenuSection(
                key: $section->key,
                label: $section->label,
                icon: $section->icon,
                permission: $section->permission,
                toolCount: 0,
                executableCount: 0,
                formReadyCount: 0,
                indexedOnlyCount: 0,
                status: 'catalog_fallback',
                databaseBacked: false,
            );
        }

        return $sections;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class)
            ?? $this->managerRegistry->getManagerForClass(AdministrationServiceSectionRecord::class);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine entity manager is configured for Administering service-tool menu records.');
        }

        return $manager;
    }

    private function labelFromKey(string $key): string
    {
        $label = preg_replace('/(?<!^)[A-Z]/', ' $0', $key) ?? $key;

        return trim($label);
    }
}
