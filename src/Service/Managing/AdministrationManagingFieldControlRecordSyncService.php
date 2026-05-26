<?php

declare(strict_types=1);

namespace App\Administering\Service\Managing;

use App\Administering\Entity\AdministrationManagingFieldControlRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Managing\AdministrationFieldAccessCatalogProviderInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes the Managing section primary CRUD anchor from field-access catalog metadata.
 */
final readonly class AdministrationManagingFieldControlRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationFieldAccessCatalogProviderInterface $fieldAccessCatalogProvider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Managing';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $this->replaceRecords();
        $count = 0;

        foreach ($this->fieldAccessCatalogProvider->catalogItems() as $item) {
            $this->entityManager->persist(new AdministrationManagingFieldControlRecord(
                resourceClass: 'managing.field.permission',
                fieldName: $item->permissionKey,
                pageName: 'all',
                subjectScope: $item->controlPlaneGroup,
                accessStatus: $item->registeredInRolling ? 'registered' : 'missing',
                visibilityStatus: $item->sensitive ? 'sensitive' : 'visible',
                safeContext: [
                    'label' => $item->label,
                    'category' => $item->category,
                    'scopes' => $item->scopes,
                ],
            ));
            ++$count;
        }

        $this->entityManager->flush();

        return new AdministrationServiceSectionAnchorSyncResult($this->sectionKey(), $count);
    }

    private function replaceRecords(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(AdministrationManagingFieldControlRecord::class, 'record')
            ->getQuery()
            ->execute();
    }
}
