<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationAclMutationApplyRecord;
use App\Administering\RepositoryInterface\AdministrationAclMutationApplyRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationAclMutationApplyRecord>
 */
final class AdministrationAclMutationApplyRecordRepository extends ServiceEntityRepository implements AdministrationAclMutationApplyRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationAclMutationApplyRecord::class);
    }
}
