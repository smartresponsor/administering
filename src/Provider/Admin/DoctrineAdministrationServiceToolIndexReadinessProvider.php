<?php

declare(strict_types=1);

namespace App\Administering\Provider\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ProviderInterface\Admin\AdministrationServiceToolIndexReadinessProviderInterface;
use App\Administering\Value\Admin\AdministrationServiceToolIndexReadinessReport;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the SQLite materialized service-tool index and reports EasyAdmin readiness.
 */
final readonly class DoctrineAdministrationServiceToolIndexReadinessProvider implements AdministrationServiceToolIndexReadinessProviderInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function report(?string $sectionFilter = null): AdministrationServiceToolIndexReadinessReport
    {
        $manager = $this->entityManager();
        $builder = $manager->createQueryBuilder()
            ->select('record')
            ->from(AdministrationServiceToolRecord::class, 'record')
            ->orderBy('record.sectionKey', 'ASC')
            ->addOrderBy('record.position', 'ASC')
            ->addOrderBy('record.toolSlug', 'ASC');

        if (null !== $sectionFilter && '' !== trim($sectionFilter)) {
            $builder
                ->andWhere('record.sectionKey = :section')
                ->setParameter('section', trim($sectionFilter));
        }

        /** @var list<AdministrationServiceToolRecord> $records */
        $records = $builder->getQuery()->getResult();
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

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine entity manager is configured for Administering service tool records. Configure the SQLite/system entity manager for App\\Administering entities.');
        }

        return $manager;
    }
}
