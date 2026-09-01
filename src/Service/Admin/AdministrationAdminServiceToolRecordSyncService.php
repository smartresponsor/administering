<?php

declare(strict_types=1);

namespace App\Administering\Service\Admin;

use App\Administering\CatalogInterface\Admin\AdministrationServiceToolCatalogInterface;
use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\Trait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Materializes canonical filesystem service tools into SQLite/Doctrine records.
 *
 * Service files remain the source of truth for tool existence. These records are
 * an EasyAdmin index/projection and must not be edited as manual tool registry data.
 */
final readonly class AdministrationAdminServiceToolRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationServiceToolCatalogInterface $toolCatalog,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Admin';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $manager = $this->entityManager();
        $existingRecords = $this->existingRecordsByToolKey($manager);
        $this->replaceRecords($manager);
        $count = 0;
        $messages = [];

        foreach ($this->toolCatalog->tools() as $position => $tool) {
            $existingRecord = $existingRecords[$tool->toolKey] ?? null;

            $record = new AdministrationServiceToolRecord(
                sectionKey: $tool->section,
                directionToken: $tool->directionToken,
                toolSlug: $tool->toolSlug,
                toolKey: $tool->toolKey,
                label: $tool->label,
                serviceClass: $tool->serviceClass,
                serviceShortName: $tool->shortName,
                serviceFile: $tool->serviceFile,
                formTypeClass: $tool->formTypeClass,
                formDataClass: $tool->formDataClass,
                operationType: $tool->operationType,
                executable: $tool->executable,
                primaryRouteName: $tool->primaryRouteName,
                primaryRouteLabel: $tool->primaryRouteLabel,
                sourceOwnership: $tool->sourceOwnership,
                ownerComponentKey: $tool->ownerComponentKey,
                ownerComponentToken: $tool->ownerComponentToken,
                ownerProviderClass: $tool->ownerProviderClass,
                ownerServiceClass: $tool->ownerServiceClass,
                ownerSourceLabel: $tool->ownerSourceLabel,
                status: $tool->executable ? 'executable' : ($tool->formTypeClass ? 'form_ready' : 'indexed'),
                enabled: $existingRecord?->isEnabled() ?? true,
                visible: $existingRecord?->isVisible() ?? true,
                position: $existingRecord?->getPosition() ?? $position + 1,
                checksum: $tool->checksum,
                safeContext: [
                    'kind' => $tool->kind,
                    'sourceOwnership' => $tool->sourceOwnership,
                    'ownerSideCandidate' => 'owner_component' === $tool->sourceOwnership,
                    'ownerComponentKey' => $tool->ownerComponentKey,
                    'ownerComponentToken' => $tool->ownerComponentToken,
                    'ownerProviderClass' => $tool->ownerProviderClass,
                    'ownerServiceClass' => $tool->ownerServiceClass,
                    'ownerSourceLabel' => $tool->ownerSourceLabel,
                    'materializedBy' => 'administering.service_tool_record_sync',
                    'materializationGuard' => 'owner_validation_errors_are_skipped_by_composite_catalog',
                    'source' => $tool->serviceFile,
                    'primaryRouteMapped' => null !== $tool->primaryRouteName,
                    'formMapped' => null !== $tool->formTypeClass,
                    'formDataMapped' => null !== $tool->formDataClass,
                    'executable' => $tool->executable,
                    'operationType' => $tool->operationType,
                    'runtimeStatePreserved' => null !== $existingRecord,
                    'labelOverridePreserved' => null !== $existingRecord?->getLabelOverride(),
                ],
            );

            if (null !== $existingRecord?->getLabelOverride()) {
                $record->configureRuntimeControls(null, null, null, $existingRecord->getLabelOverride());
            }

            $manager->persist($record);
            ++$count;
        }

        $manager->flush();
        $messages[] = 'Synchronized strict convention-matched service tools into administration_service_tool_record while preserving existing runtime visibility/order/label override flags by toolKey.';

        return new AdministrationServiceSectionAnchorSyncResult('ServiceTool', $count, 'synced', $messages);
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('No Doctrine entity manager is configured for Administering service tool records. Configure the SQLite/system entity manager for App\\Administering entities.');
        }

        return $manager;
    }

    /** @return array<string, AdministrationServiceToolRecord> */
    private function existingRecordsByToolKey(EntityManagerInterface $manager): array
    {
        $records = $manager->getRepository(AdministrationServiceToolRecord::class)->findAll();
        $indexed = [];

        foreach ($records as $record) {
            $indexed[$record->getToolKey()] = $record;
        }

        return $indexed;
    }

    private function replaceRecords(EntityManagerInterface $manager): void
    {
        $manager->createQueryBuilder()
            ->delete(AdministrationServiceToolRecord::class, 'record')
            ->getQuery()
            ->execute();
    }
}
