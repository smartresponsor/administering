<?php

declare(strict_types=1);

namespace App\Administering\Service\Accessing;

use App\Administering\Entity\AdministrationAccessingAccountRecord;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes the Accessing section primary CRUD anchor from safe account projections.
 */
final readonly class AdministrationAccessingAccountRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationAccountProjectionProviderInterface $accountProjectionProvider,
        private EntityManagerInterface $entityManager,
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

        foreach ($this->accountProjectionProvider->recent(100) as $account) {
            $status = $account->active() ? ($account->verified() ? 'active_verified' : 'active_unverified') : 'inactive';
            $this->entityManager->persist(new AdministrationAccessingAccountRecord(
                accountReference: $account->subjectId(),
                displayLabel: $account->displayName() ?? $account->identifier(),
                status: $status,
                provider: 'Accessing',
                safeContext: [
                    'identifier' => $account->identifier(),
                    'bootstrapRoles' => $account->bootstrapRoles(),
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
            ->delete(AdministrationAccessingAccountRecord::class, 'record')
            ->getQuery()
            ->execute();
    }
}
