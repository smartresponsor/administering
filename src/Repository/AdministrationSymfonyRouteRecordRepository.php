<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationSymfonyRouteRecord;
use App\Administering\RepositoryInterface\AdministrationSymfonyRouteRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationSymfonyRouteRecord>
 */
final class AdministrationSymfonyRouteRecordRepository extends ServiceEntityRepository implements AdministrationSymfonyRouteRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationSymfonyRouteRecord::class);
    }
}
