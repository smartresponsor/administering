<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationOperationEvent;
use App\Administering\RepositoryInterface\AdministrationOperationEventRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationOperationEvent>
 */
final class AdministrationOperationEventRepository extends ServiceEntityRepository implements AdministrationOperationEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationOperationEvent::class);
    }
}
