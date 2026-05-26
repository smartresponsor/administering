<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldAccessCatalogItem;
use App\Managing\Value\Administration\ManagingFieldAccessMatrixRow;

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
