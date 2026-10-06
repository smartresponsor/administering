<?php

declare(strict_types=1);

namespace App\Administering\Service\Environment;

use App\Administering\Entity\AdministrationEnvironmentRuntimeRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Environment\AdministrationEnvironmentRuntimeStatusProviderInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;

/**
 * Synchronizes the Environment primary CRUD anchor from safe runtime metadata.
 */
final readonly class AdministrationEnvironmentRuntimeRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationEnvironmentRuntimeStatusProviderInterface $runtimeStatusProvider,
        private AdministrationPersistenceRepository $persistenceRepository,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Environment';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $this->replaceRecords();
        $count = 0;
        $records = [];

        foreach ($this->runtimeStatusProvider->status() as $key => $value) {
            $records[] = new AdministrationEnvironmentRuntimeRecordEntity(
                environmentKey: (string) $key,
                category: 'runtime',
                status: 'available',
                sourceType: $this->sourceType((string) $key),
                safeContext: ['value' => (string) $value],
            );
            ++$count;
        }

        $this->persistenceRepository->persistAll($records, AdministrationEnvironmentRuntimeRecordEntity::class);

        return new AdministrationServiceSectionAnchorSyncResult($this->sectionKey(), $count);
    }

    private function sourceType(string $key): string
    {
        return match ($key) {
            'environment', 'debug' => 'kernel',
            'phpVersion' => 'php',
            default => 'runtime',
        };
    }

    private function replaceRecords(): void
    {
        $this->persistenceRepository->deleteBy(AdministrationEnvironmentRuntimeRecordEntity::class, []);
    }
}
