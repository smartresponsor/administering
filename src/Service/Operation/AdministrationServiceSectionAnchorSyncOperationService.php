<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\Service\Accessing\AccessingAdministrationAccountRecordSyncService;
use App\Administering\Service\Connected\AdministrationConnectedComponentRecordSyncService;
use App\Administering\Service\Environment\AdministrationEnvironmentRuntimeRecordSyncService;
use App\Administering\Service\Managing\AdministrationManagingFieldControlRecordSyncService;
use App\Administering\Service\Symfony\AdministrationSymfonyRouteRecordSyncService;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Operation\AdministrationServiceSectionAnchorSyncOperationServiceInterface;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;

/**
 * Operation-facing service that synchronizes primary CRUD anchor records for service sections.
 */
final class AdministrationServiceSectionAnchorSyncOperationService implements AdministrationServiceSectionAnchorSyncOperationServiceInterface
{
    /** @var array<string, AdministrationServiceSectionAnchorSyncServiceInterface> */
    private array $syncServices = [];

    public function __construct(
        AccessingAdministrationAccountRecordSyncService $accessingSyncService,
        AdministrationConnectedComponentRecordSyncService $connectedSyncService,
        AdministrationEnvironmentRuntimeRecordSyncService $environmentSyncService,
        AdministrationManagingFieldControlRecordSyncService $managingSyncService,
        AdministrationSymfonyRouteRecordSyncService $symfonySyncService,
    ) {
        foreach ([$accessingSyncService, $connectedSyncService, $environmentSyncService, $managingSyncService, $symfonySyncService] as $service) {
            $this->syncServices[$service->sectionKey()] = $service;
        }
    }

    /** @return list<AdministrationServiceSectionAnchorSyncResult> */
    public function synchronize(?string $section = null): array
    {
        $services = $this->servicesToRun($section);
        if ([] === $services) {
            return [new AdministrationServiceSectionAnchorSyncResult(
                $section ?? 'all',
                0,
                'skipped',
                [sprintf('Unknown section. Available sections: %s.', implode(', ', $this->supportedSections()))],
            )];
        }

        return array_map(static fn (AdministrationServiceSectionAnchorSyncServiceInterface $service): AdministrationServiceSectionAnchorSyncResult => $service->synchronize(), $services);
    }

    /** @return list<string> */
    public function supportedSections(): array
    {
        return array_keys($this->syncServices);
    }

    /** @return list<AdministrationServiceSectionAnchorSyncServiceInterface> */
    private function servicesToRun(?string $section): array
    {
        if (null === $section || '' === trim($section)) {
            return array_values($this->syncServices);
        }

        $normalized = ucfirst(strtolower(trim($section)));

        return isset($this->syncServices[$normalized]) ? [$this->syncServices[$normalized]] : [];
    }
}
