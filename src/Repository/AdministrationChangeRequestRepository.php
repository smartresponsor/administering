<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationChangeRequest;
use App\Administering\RepositoryInterface\AdministrationChangeRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationChangeRequest>
 */
final class AdministrationChangeRequestRepository extends ServiceEntityRepository implements AdministrationChangeRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationChangeRequest::class);
    }
}
