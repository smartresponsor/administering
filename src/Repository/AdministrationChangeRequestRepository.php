<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationChangeRequestEntity;
use App\Administering\RepositoryInterface\AdministrationChangeRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationChangeRequestEntity>
 */
final class AdministrationChangeRequestRepository extends ServiceEntityRepository implements AdministrationChangeRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationChangeRequestEntity::class);
    }
}
