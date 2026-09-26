<?php

declare(strict_types=1);

namespace App\Administering\Provider\Admin;

use App\Administering\Entity\AdministrationServiceToolRecordEntity;
use App\Administering\ProviderInterface\Admin\AdministrationServiceToolIndexReadinessProviderInterface;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\Value\Admin\AdministrationServiceToolIndexReadinessReport;

/**
 * Reads the SQLite materialized service-tool index and reports EasyAdmin readiness.
 */
final readonly class AdministrationDoctrineServiceToolIndexReadinessProvider implements AdministrationServiceToolIndexReadinessProviderInterface
{
    public function __construct(private AdministrationPersistenceRepository $persistenceRepository)
    {
    }

    public function report(?string $sectionFilter = null): AdministrationServiceToolIndexReadinessReport
    {
        $criteria = [];
        if (null !== $sectionFilter && '' !== trim($sectionFilter)) {
            $criteria['sectionKey'] = trim($sectionFilter);
        }

        /** @var list<AdministrationServiceToolRecordEntity> $records */
        $records = $this->persistenceRepository->findBy(
            AdministrationServiceToolRecordEntity::class,
            $criteria,
            ['sectionKey' => 'ASC', 'position' => 'ASC', 'toolSlug' => 'ASC'],
        );
        $statusCounts = [];
        $rows = [];
        $executableCount = 0;
        $formReadyCount = 0;
        $indexedOnlyCount = 0;

        foreach ($records as $record) {
            $status = $record->getStatus();
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if ($record->isExecutable()) {
                ++$executableCount;
            } elseif (null !== $record->getFormTypeClass()) {
                ++$formReadyCount;
            } else {
                ++$indexedOnlyCount;
            }

            $rows[] = [
                'sectionKey' => $record->getSectionKey(),
                'toolKey' => $record->getToolKey(),
                'toolSlug' => $record->getToolSlug(),
                'generatedLabel' => $record->getGeneratedLabel(),
                'labelOverride' => $record->getLabelOverride(),
                'displayLabel' => $record->getDisplayLabel(),
                'status' => $status,
                'executable' => $record->isExecutable(),
                'formTypeClass' => $record->getFormTypeClass(),
                'formDataClass' => $record->getFormDataClass(),
                'serviceClass' => $record->getServiceClass(),
                'serviceFile' => $record->getServiceFile(),
                'sourceOwnership' => $record->getSourceOwnership(),
                'sourceLabel' => $record->getSourceLabel(),
                'ownerComponentKey' => $record->getOwnerComponentKey(),
                'ownerComponentToken' => $record->getOwnerComponentToken(),
                'ownerProviderClass' => $record->getOwnerProviderClass(),
                'ownerServiceClass' => $record->getOwnerServiceClass(),
                'ownerSourceLabel' => $record->getOwnerSourceLabel(),
            ];
        }

        ksort($statusCounts);

        return new AdministrationServiceToolIndexReadinessReport(
            sectionFilter: $sectionFilter,
            totalCount: count($records),
            executableCount: $executableCount,
            formReadyCount: $formReadyCount,
            indexedOnlyCount: $indexedOnlyCount,
            statusCounts: $statusCounts,
            records: $rows,
        );
    }
}
