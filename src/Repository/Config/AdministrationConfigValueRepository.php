<?php

declare(strict_types=1);

namespace App\Administering\Repository\Config;

use App\Administering\Entity\Config\AdministrationConfigValue;
use App\Administering\RepositoryInterface\Config\AdministrationConfigValueRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationConfigValue>
 */
final class AdministrationConfigValueRepository extends ServiceEntityRepository implements AdministrationConfigValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationConfigValue::class);
    }
}
