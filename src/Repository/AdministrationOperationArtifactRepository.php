<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationOperationArtifact;
use App\Administering\RepositoryInterface\AdministrationOperationArtifactRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationOperationArtifact>
 */
final class AdministrationOperationArtifactRepository extends ServiceEntityRepository implements AdministrationOperationArtifactRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationOperationArtifact::class);
    }
}
