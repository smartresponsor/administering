<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Environment;

interface AdministrationEnvironmentRuntimeStatusProviderInterface
{
    /** @return array{environment:string,debug:bool,phpVersion:string} */
    public function status(): array;
}
