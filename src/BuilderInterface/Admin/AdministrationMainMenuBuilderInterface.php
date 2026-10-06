<?php

declare(strict_types=1);

namespace App\Administering\BuilderInterface\Admin;

/**
 * Defines the navigation-builder boundary consumed by the Administering dashboard.
 */
interface AdministrationMainMenuBuilderInterface
{
    /**
     * Builds the ordered operator-facing menu without coupling the dashboard to its source catalogs.
     *
     * @return iterable<object>
     */
    public function build(): iterable;
}
