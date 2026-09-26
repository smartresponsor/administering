<?php

declare(strict_types=1);

namespace App\Administering\Service\Admin;

use App\Administering\Entity\AdministrationServiceToolRecordEntity;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolRecordStorageInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AdministrationDoctrineServiceToolRecordStorage implements AdministrationServiceToolRecordStorageInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function findOneByToolKey(string $toolKey): ?AdministrationServiceToolRecordEntity
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecordEntity::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering service tool records.');
        }

        $record = $manager->getRepository(AdministrationServiceToolRecordEntity::class)->findOneBy(['toolKey' => $toolKey]);

        return $record instanceof AdministrationServiceToolRecordEntity ? $record : null;
    }

    public function flush(): void
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecordEntity::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering service tool records.');
        }

        $manager->flush();
    }
}
