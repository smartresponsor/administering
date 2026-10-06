<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationEnvironmentRuntimeRecordEntity;
use App\Administering\RepositoryInterface\AdministrationEnvironmentRuntimeRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationEnvironmentRuntimeRecordEntity>
 */
final class AdministrationEnvironmentRuntimeRecordRepository extends ServiceEntityRepository implements AdministrationEnvironmentRuntimeRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationEnvironmentRuntimeRecordEntity::class);
    }
}
