<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationCredentialDefinition;
use App\Administering\RepositoryInterface\AdministrationCredentialDefinitionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationCredentialDefinition>
 */
final class AdministrationCredentialDefinitionRepository extends ServiceEntityRepository implements AdministrationCredentialDefinitionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationCredentialDefinition::class);
    }
}
