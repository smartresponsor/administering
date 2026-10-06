<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationServiceToolRecordEntity;
use App\Administering\RepositoryInterface\AdministrationServiceToolRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationServiceToolRecordEntity>
 */
final class AdministrationServiceToolRecordRepository extends ServiceEntityRepository implements AdministrationServiceToolRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationServiceToolRecordEntity::class);
    }
}
