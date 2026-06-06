<?php

declare(strict_types=1);

namespace App\Administering\Service\Connected;

use App\Administering\Entity\AdministrationConnectedComponentRecord;
use App\Administering\Service\RuntimeScope\AdministrationRuntimeScopeDecisionService;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use App\Administering\Value\RuntimeScope\AdministrationRuntimeScopeDecisionRow;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes the Enabled Components CRUD anchor from runtime-scope decisions.
 */
final readonly class AdministrationConnectedComponentRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private string $projectDir,
        private string $environment,
        private AdministrationRuntimeScopeDecisionService $decisionService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Connected';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $this->replaceRecords();

        $devRows = $this->decisionService->decide($this->projectDir, 'dev')->decisionRowsByComponent();
        $prodRows = $this->decisionService->decide($this->projectDir, 'prod')->decisionRowsByComponent();
        $componentKeys = array_values(array_unique(array_merge(array_keys($devRows), array_keys($prodRows))));
        sort($componentKeys);

        $count = 0;
        foreach ($componentKeys as $componentKey) {
            $current = 'prod' === $this->environment ? ($prodRows[$componentKey] ?? null) : ($devRows[$componentKey] ?? null);
            $dev = $devRows[$componentKey] ?? null;
            $prod = $prodRows[$componentKey] ?? null;

            $status = $current?->status ?? $dev?->status ?? $prod?->status ?? 'unknown';
            $readiness = in_array($status, ['available', 'reportable', 'auditable'], true) ? 'ready' : 'review';

            $this->entityManager->persist(new AdministrationConnectedComponentRecord(
                componentName: $componentKey,
                status: $status,
                readinessStatus: $readiness,
                safeSummary: [
                    'message' => $this->message($dev, $prod, $current),
                    'metadata' => [
                        ...$this->flatMetadata($current),
                        'currentEnvironment' => $this->environment,
                        'dev' => $dev?->toArray(),
                        'prod' => $prod?->toArray(),
                    ],
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
            ->delete(AdministrationConnectedComponentRecord::class, 'record')
            ->getQuery()
            ->execute();
    }

    /** @return array<string, mixed> */
    private function flatMetadata(?AdministrationRuntimeScopeDecisionRow $row): array
    {
        if (null === $row) {
            return [
                'source' => 'runtime_scope_evidence',
                'present' => false,
                'allowed' => false,
                'locked' => false,
                'enabled' => false,
                'disabled' => false,
                'runtimeScope' => '',
                'composerPackage' => null,
                'bundleToken' => null,
            ];
        }

        return [
            'source' => 'runtime_scope_evidence',
            'present' => $row->present,
            'allowed' => $row->allowed,
            'locked' => $row->locked,
            'enabled' => $row->enabled,
            'disabled' => $row->disabled,
            'runtimeScope' => $row->runtimeScope,
            'composerPackage' => $row->composerPackage,
            'bundleToken' => $row->bundleToken,
        ];
    }

    private function message(
        ?AdministrationRuntimeScopeDecisionRow $dev,
        ?AdministrationRuntimeScopeDecisionRow $prod,
        ?AdministrationRuntimeScopeDecisionRow $current,
    ): string {
        if (null !== $current) {
            return $current->message;
        }

        return sprintf(
            'Dev: %s; Prod: %s',
            $dev?->status ?? 'unknown',
            $prod?->status ?? 'unknown',
        );
    }
}
