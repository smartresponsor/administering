<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationCredentialState;
use App\Administering\RepositoryInterface\AdministrationCredentialStateRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationCredentialState>
 */
final class AdministrationCredentialStateRepository extends ServiceEntityRepository implements AdministrationCredentialStateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationCredentialState::class);
    }
}
