<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationAccessingAccountRecordEntity;
use App\Administering\RepositoryInterface\AdministrationAccessingAccountRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationAccessingAccountRecordEntity>
 */
final class AdministrationAccessingAccountRecordRepository extends ServiceEntityRepository implements AdministrationAccessingAccountRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationAccessingAccountRecordEntity::class);
    }
}
