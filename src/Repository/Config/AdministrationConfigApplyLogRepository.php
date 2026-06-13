<?php

declare(strict_types=1);

namespace App\Administering\Repository\Config;

use App\Administering\Entity\Config\AdministrationConfigApplyLog;
use App\Administering\RepositoryInterface\Config\AdministrationConfigApplyLogRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationConfigApplyLog>
 */
final class AdministrationConfigApplyLogRepository extends ServiceEntityRepository implements AdministrationConfigApplyLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationConfigApplyLog::class);
    }
}
