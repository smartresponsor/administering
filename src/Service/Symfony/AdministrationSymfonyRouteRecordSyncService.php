<?php

declare(strict_types=1);

namespace App\Administering\Service\Symfony;

use App\Administering\Entity\AdministrationSymfonyRouteRecordEntity;
use App\Administering\Repository\AdministrationPersistenceRepository;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolHandlerInterface;
use App\Administering\ServiceInterface\Symfony\AdministrationSymfonyRouteCatalogProviderInterface;
use App\Administering\ServiceTrait\Admin\AdministrationServiceSectionAnchorSyncToolHandlerTrait;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;

/**
 * Synchronizes the Symfony section primary CRUD anchor from route metadata.
 */
final readonly class AdministrationSymfonyRouteRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface, AdministrationServiceToolHandlerInterface
{
    use AdministrationServiceSectionAnchorSyncToolHandlerTrait;

    public function __construct(
        private AdministrationSymfonyRouteCatalogProviderInterface $routeCatalogProvider,
        private AdministrationPersistenceRepository $persistenceRepository,
    ) {
    }

    public function sectionKey(): string
    {
        return 'Symfony';
    }

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult
    {
        $this->replaceRecords();
        $count = 0;
        $records = [];

        foreach ($this->routeCatalogProvider->routes() as $route) {
            $records[] = new AdministrationSymfonyRouteRecordEntity(
                routeName: (string) $route['route'],
                path: (string) $route['path'],
                methods: $this->methods($route['methods']),
                controller: null,
                statusCode: null,
                statusClass: 'unchecked',
            );
            ++$count;
        }

        $this->persistenceRepository->persistAll($records, AdministrationSymfonyRouteRecordEntity::class);

        return new AdministrationServiceSectionAnchorSyncResult($this->sectionKey(), $count);
    }

    /** @param mixed $methods @return list<string> */
    /**
     * @return list<string>
     */
    private function methods(mixed $methods): array
    {
        if (!is_array($methods)) {
            return ['ANY'];
        }

        $normalized = array_values(array_filter(array_map('strval', $methods)));

        return [] !== $normalized ? $normalized : ['ANY'];
    }

    private function replaceRecords(): void
    {
        $this->persistenceRepository->deleteBy(AdministrationSymfonyRouteRecordEntity::class, []);
    }
}
