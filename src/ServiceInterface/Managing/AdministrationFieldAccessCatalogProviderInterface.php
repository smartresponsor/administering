<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationManagingFieldAccessCatalogItem;
use App\Administering\Value\Managing\AdministrationManagingFieldAccessMatrixRow;

/**
 * Provides read-only control-plane metadata for Managing field access administration.
 */
interface AdministrationFieldAccessCatalogProviderInterface
{
    /** @return list<AdministrationManagingFieldAccessCatalogItem> */
    public function catalogItems(): array;

    /** @return list<AdministrationManagingFieldAccessMatrixRow> */
    public function matrixRows(): array;
}
