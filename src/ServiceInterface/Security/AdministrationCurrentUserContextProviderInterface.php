<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Security;

use App\Administering\Value\AdministrationCurrentUserContext;

interface AdministrationCurrentUserContextProviderInterface
{
    public function current(): ?AdministrationCurrentUserContext;
}
