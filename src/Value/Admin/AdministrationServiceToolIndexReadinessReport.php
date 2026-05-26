<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Read-only summary of the materialized service-tool index stored in SQLite.
 *
 * The report is intentionally derived from AdministrationServiceToolRecord rows,
 * not directly from src/Service, so it verifies what EasyAdmin will actually see
 * after refresh-index has synchronized the filesystem catalog.
 */
final readonly class AdministrationServiceToolIndexReadinessReport
{
    /**
     * @param array<string, int>         $statusCounts
     * @param list<array<string, mixed>> $records
     */
    public function __construct(
        public ?string $sectionFilter,
        public int $totalCount,
        public int $executableCount,
        public int $formReadyCount,
        public int $indexedOnlyCount,
        public array $statusCounts,
        public array $records,
    ) {
    }

    public function isFullyExecutable(): bool
    {
        return $this->totalCount > 0 && $this->totalCount === $this->executableCount;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sectionFilter' => $this->sectionFilter,
            'totalCount' => $this->totalCount,
            'executableCount' => $this->executableCount,
            'formReadyCount' => $this->formReadyCount,
            'indexedOnlyCount' => $this->indexedOnlyCount,
            'statusCounts' => $this->statusCounts,
            'fullyExecutable' => $this->isFullyExecutable(),
            'records' => $this->records,
        ];
    }
}
