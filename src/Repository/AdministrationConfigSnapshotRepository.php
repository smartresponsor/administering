<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationConfigSnapshotEntity;
use App\Administering\RepositoryInterface\AdministrationConfigSnapshotRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationConfigSnapshotEntity>
 */
final class AdministrationConfigSnapshotRepository extends ServiceEntityRepository implements AdministrationConfigSnapshotRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationConfigSnapshotEntity::class);
    }
}
