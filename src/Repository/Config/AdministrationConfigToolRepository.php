<?php

declare(strict_types=1);

namespace App\Administering\Repository\Config;

use App\Administering\Entity\Config\AdministrationConfigTool;
use App\Administering\RepositoryInterface\Config\AdministrationConfigToolRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationConfigTool>
 */
final class AdministrationConfigToolRepository extends ServiceEntityRepository implements AdministrationConfigToolRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationConfigTool::class);
    }
}
