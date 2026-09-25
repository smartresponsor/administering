<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationServiceSectionRecordEntity;
use App\Administering\RepositoryInterface\AdministrationServiceSectionRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationServiceSectionRecordEntity>
 */
final class AdministrationServiceSectionRecordRepository extends ServiceEntityRepository implements AdministrationServiceSectionRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationServiceSectionRecordEntity::class);
    }
}
