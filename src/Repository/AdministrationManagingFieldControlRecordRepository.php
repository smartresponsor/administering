<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationManagingFieldControlRecord;
use App\Administering\RepositoryInterface\AdministrationManagingFieldControlRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationManagingFieldControlRecord>
 */
final class AdministrationManagingFieldControlRecordRepository extends ServiceEntityRepository implements AdministrationManagingFieldControlRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationManagingFieldControlRecord::class);
    }
}
