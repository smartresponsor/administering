<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldAccessCatalogItem;
use App\Administering\Value\Managing\ManagingFieldAccessMatrixRow;

/**
 * Provides read-only control-plane metadata for Managing field access administration.
 */
interface AdministrationFieldAccessCatalogProviderInterface
{
    /** @return list<ManagingFieldAccessCatalogItem> */
    public function catalogItems(): array;

    /** @return list<ManagingFieldAccessMatrixRow> */
    public function matrixRows(): array;
}
