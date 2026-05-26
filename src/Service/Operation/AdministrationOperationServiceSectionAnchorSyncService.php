<?php

declare(strict_types=1);

namespace App\Administering\Service\Operation;

use App\Administering\Service\Accessing\AdministrationAccessingAccountRecordSyncService;
use App\Administering\Service\Admin\AdministrationAdminServiceToolRecordSyncService;
use App\Administering\Service\Connected\AdministrationConnectedComponentRecordSyncService;
use App\Administering\Service\Environment\AdministrationEnvironmentRuntimeRecordSyncService;
use App\Administering\Service\Managing\AdministrationManagingFieldControlRecordSyncService;
use App\Administering\Service\Symfony\AdministrationSymfonyRouteRecordSyncService;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Operation\AdministrationServiceSectionAnchorSyncOperationServiceInterface;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use App\Administering\Value\Admin\AdministrationServiceToolInvocation;
use App\Administering\Value\Operation\AdministrationOperationExecutionResult;

/**
 * Operation-facing service that synchronizes primary CRUD anchor records for service sections.
 */
final class AdministrationOperationServiceSectionAnchorSyncService implements AdministrationServiceSectionAnchorSyncOperationServiceInterface, AdministrationServiceToolHandlerInterface
{
    /** @var array<string, AdministrationServiceSectionAnchorSyncServiceInterface> */
    private array $syncServices = [];

    public function __construct(
        AdministrationAccessingAccountRecordSyncService $accessingSyncService,
        AdministrationAdminServiceToolRecordSyncService $serviceToolSyncService,
        AdministrationConnectedComponentRecordSyncService $connectedSyncService,
        AdministrationEnvironmentRuntimeRecordSyncService $environmentSyncService,
        AdministrationManagingFieldControlRecordSyncService $managingSyncService,
        AdministrationSymfonyRouteRecordSyncService $symfonySyncService,
    ) {
        foreach ([$accessingSyncService, $serviceToolSyncService, $connectedSyncService, $environmentSyncService, $managingSyncService, $symfonySyncService] as $service) {
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

    public function handleAdministrationServiceTool(AdministrationServiceToolInvocation $invocation): AdministrationOperationExecutionResult
    {
        $section = $invocation->stringFormValue('section');
        $results = $this->synchronize('' !== $section ? $section : null);
        $hasOnlySkippedResults = [] !== $results && [] === array_filter($results, static fn (AdministrationServiceSectionAnchorSyncResult $result): bool => 'skipped' !== $result->status);
        $safeContext = [
            'tool_key' => $invocation->toolKey,
            'section_key' => $invocation->sectionKey,
            'tool_slug' => $invocation->toolSlug,
            'requested_section' => '' !== $section ? $section : 'all',
            'results' => array_map(static fn (AdministrationServiceSectionAnchorSyncResult $result): array => $result->toArray(), $results),
        ];

        if ($hasOnlySkippedResults) {
            return AdministrationOperationExecutionResult::skipped('No matching service section anchor sync service was found.', $safeContext);
        }

        return AdministrationOperationExecutionResult::succeeded('Synchronized service section anchors.', $safeContext);
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
