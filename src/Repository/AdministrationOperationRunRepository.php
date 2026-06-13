<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationOperationRun;
use App\Administering\RepositoryInterface\AdministrationOperationRunRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationOperationRun>
 */
final class AdministrationOperationRunRepository extends ServiceEntityRepository implements AdministrationOperationRunRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationOperationRun::class);
    }
}
