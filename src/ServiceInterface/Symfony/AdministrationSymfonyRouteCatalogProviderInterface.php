<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Symfony;

interface AdministrationSymfonyRouteCatalogProviderInterface
{
    /** @return list<array{route:string,path:string,methods:list<string>}> */
    public function routes(): array;
}
