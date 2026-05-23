<?php

declare(strict_types=1);

namespace App\Administering\Service\Symfony;

use App\Administering\Entity\AdministrationSymfonyRouteRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceSectionAnchorSyncServiceInterface;
use App\Administering\ServiceInterface\Symfony\AdministrationSymfonyRouteCatalogProviderInterface;
use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Synchronizes the Symfony section primary CRUD anchor from route metadata.
 */
final readonly class AdministrationSymfonyRouteRecordSyncService implements AdministrationServiceSectionAnchorSyncServiceInterface
{
    public function __construct(
        private AdministrationSymfonyRouteCatalogProviderInterface $routeCatalogProvider,
        private EntityManagerInterface $entityManager,
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

        foreach ($this->routeCatalogProvider->routes() as $route) {
            $this->entityManager->persist(new AdministrationSymfonyRouteRecord(
                routeName: (string) $route['route'],
                path: (string) $route['path'],
                methods: $this->methods($route['methods'] ?? []),
                controller: isset($route['controller']) ? (string) $route['controller'] : null,
                statusCode: null,
                statusClass: 'unchecked',
            ));
            ++$count;
        }

        $this->entityManager->flush();

        return new AdministrationServiceSectionAnchorSyncResult($this->sectionKey(), $count);
    }

    /** @param mixed $methods @return list<string> */
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
        $this->entityManager->createQueryBuilder()
            ->delete(AdministrationSymfonyRouteRecord::class, 'record')
            ->getQuery()
            ->execute();
    }
}
