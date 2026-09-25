<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationConnectedComponentRecordEntity;
use App\Administering\RepositoryInterface\AdministrationConnectedComponentRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationConnectedComponentRecordEntity>
 */
final class AdministrationConnectedComponentRecordRepository extends ServiceEntityRepository implements AdministrationConnectedComponentRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationConnectedComponentRecordEntity::class);
    }
}
