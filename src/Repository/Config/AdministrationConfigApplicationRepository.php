<?php

declare(strict_types=1);

namespace App\Administering\Repository\Config;

use App\Administering\Entity\Config\AdministrationConfigApplication;
use App\Administering\RepositoryInterface\Config\AdministrationConfigApplicationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdministrationConfigApplication>
 */
final class AdministrationConfigApplicationRepository extends ServiceEntityRepository implements AdministrationConfigApplicationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrationConfigApplication::class);
    }
}
