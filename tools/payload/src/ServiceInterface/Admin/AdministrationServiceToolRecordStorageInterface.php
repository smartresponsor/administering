<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Entity\AdministrationServiceToolRecord;

interface AdministrationServiceToolRecordStorageInterface
{
    public function findOneByToolKey(string $toolKey): ?AdministrationServiceToolRecord;

    public function flush(): void;
}
