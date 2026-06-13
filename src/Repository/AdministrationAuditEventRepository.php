<?php

declare(strict_types=1);

namespace App\Administering\Repository;

use App\Administering\Entity\AdministrationAuditEvent;
use App\Administering\RepositoryInterface\AdministrationAuditEventRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationAuditEvent>
 */
final class AdministrationAuditEventRepository extends ServiceEntityRepository implements AdministrationAuditEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationAuditEvent::class);
    }
}
