<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationOperationRunEntity;
use App\Administering\RepositoryInterface\AdministrationOperationRunRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationOperationRunEntity>
 */
final class AdministrationOperationRunRepository extends ServiceEntityRepository implements AdministrationOperationRunRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationOperationRunEntity::class);
    }
}
