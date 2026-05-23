<?php

declare(strict_types=1);

namespace App\Administering\BuilderInterface\Admin;

interface AdministrationMainMenuBuilderInterface
{
    /** @return iterable<object> */
    public function build(): iterable;
}
