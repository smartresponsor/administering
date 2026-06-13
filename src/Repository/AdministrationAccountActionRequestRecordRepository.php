<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationAccountActionRequestRecord;
use App\Administering\RepositoryInterface\AdministrationAccountActionRequestRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationAccountActionRequestRecord>
 */
final class AdministrationAccountActionRequestRecordRepository extends ServiceEntityRepository implements AdministrationAccountActionRequestRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationAccountActionRequestRecord::class);
    }
}
