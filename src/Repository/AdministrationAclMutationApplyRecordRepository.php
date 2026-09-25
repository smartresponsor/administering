<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationAclMutationApplyRecordEntity;
use App\Administering\RepositoryInterface\AdministrationAclMutationApplyRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationAclMutationApplyRecordEntity>
 */
final class AdministrationAclMutationApplyRecordRepository extends ServiceEntityRepository implements AdministrationAclMutationApplyRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationAclMutationApplyRecordEntity::class);
    }
}
