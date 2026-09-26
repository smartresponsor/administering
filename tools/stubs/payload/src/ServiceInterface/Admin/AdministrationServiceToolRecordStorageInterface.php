<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Entity\AdministrationServiceToolRecordEntity;

interface AdministrationServiceToolRecordStorageInterface
{
    public function findOneByToolKey(string $toolKey): ?AdministrationServiceToolRecordEntity;

    public function flush(): void;
}
