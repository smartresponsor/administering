<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Rolling\AdministrationFieldAccessCatalogItem;
use App\Administering\Value\Rolling\AdministrationFieldAccessMatrixRow;

/**
 * Provides read-only control-plane metadata for Managing field access administration.
 */
interface AdministrationFieldAccessCatalogProviderInterface
{
    /** @return list<AdministrationFieldAccessCatalogItem> */
    public function catalogItems(): array;

    /** @return list<AdministrationFieldAccessMatrixRow> */
    public function matrixRows(): array;
}
