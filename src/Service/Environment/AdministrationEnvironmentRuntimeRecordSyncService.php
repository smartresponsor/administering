<?php

declare(strict_types=1);

namespace App\Administering\Service\Environment;

use App\Administering\Entity\AdministrationEnvironmentRuntimeRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Environment\AdministrationEnvironmentRuntimeStatusProviderInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes the Environment primary CRUD anchor from safe runtime metadata.
 */
final readonly class AdministrationEnvironmentRuntimeRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationEnvironmentRuntimeStatusProviderInterface $runtimeStatusProvider,
        private EntityManagerInterface $entityManager,
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

        foreach ($this->runtimeStatusProvider->status() as $key => $value) {
            $this->entityManager->persist(new AdministrationEnvironmentRuntimeRecord(
                environmentKey: (string) $key,
                category: 'runtime',
                status: 'available',
                sourceType: $this->sourceType((string) $key),
                safeContext: ['value' => is_scalar($value) ? (string) $value : get_debug_type($value)],
            ));
            ++$count;
        }

        $this->entityManager->flush();

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
        $this->entityManager->createQueryBuilder()
            ->delete(AdministrationEnvironmentRuntimeRecord::class, 'record')
            ->getQuery()
            ->execute();
    }
}
