<?php

declare(strict_types=1);

namespace App\Administering\Service\Accessing;

use App\Administering\Entity\AdministrationAccessingAccountRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;

/**
 * Synchronizes the Accessing section primary CRUD anchor from safe account projections.
 */
final readonly class AdministrationAccessingAccountRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationAccountProjectionProviderInterface $accountProjectionProvider,
        private AdministrationPersistenceRepository $persistenceRepository,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Accessing';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $this->replaceRecords();
        $count = 0;
        $records = [];

        foreach ($this->accountProjectionProvider->recent(100) as $account) {
            $status = $account->active() ? ($account->verified() ? 'active_verified' : 'active_unverified') : 'inactive';
            $records[] = new AdministrationAccessingAccountRecordEntity(
                accountReference: $account->subjectId(),
                displayLabel: $account->displayName() ?? $account->identifier(),
                status: $status,
                provider: 'Accessing',
                safeContext: [
                    'identifier' => $account->identifier(),
                    'bootstrapRoles' => $account->bootstrapRoles(),
                ],
            );
            ++$count;
        }

        $this->persistenceRepository->persistAll($records, AdministrationAccessingAccountRecordEntity::class);

        return new AdministrationServiceSectionAnchorSyncResult($this->sectionKey(), $count);
    }

    private function replaceRecords(): void
    {
        $this->persistenceRepository->deleteBy(AdministrationAccessingAccountRecordEntity::class, []);
    }
}
