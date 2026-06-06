<?php

declare(strict_types=1);

namespace App\Administering\Service\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;
use App\Administering\ServiceInterface\Admin\AdministrationServiceToolRecordStorageInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class AdministrationDoctrineServiceToolRecordStorage implements AdministrationServiceToolRecordStorageInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function findOneByToolKey(string $toolKey): ?AdministrationServiceToolRecord
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering service tool records.');
        }

        $record = $manager->getRepository(AdministrationServiceToolRecord::class)->findOneBy(['toolKey' => $toolKey]);

        return $record instanceof AdministrationServiceToolRecord ? $record : null;
    }

    public function flush(): void
    {
        $manager = $this->managerRegistry->getManagerForClass(AdministrationServiceToolRecord::class);
        if (null === $manager) {
            throw new \LogicException('No Doctrine manager is configured for Administering service tool records.');
        }

        $manager->flush();
    }
}
