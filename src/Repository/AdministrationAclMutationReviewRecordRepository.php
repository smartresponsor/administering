<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationAclMutationReviewRecordEntity;
use App\Administering\RepositoryInterface\AdministrationAclMutationReviewRecordRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationAclMutationReviewRecordEntity>
 */
final class AdministrationAclMutationReviewRecordRepository extends ServiceEntityRepository implements AdministrationAclMutationReviewRecordRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationAclMutationReviewRecordEntity::class);
    }
}
