<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\ManagingFieldViewProfileCatalogItem;
use App\Administering\Value\Managing\ManagingFieldViewProfilePriorityRow;
use App\Administering\Value\Managing\ManagingFieldViewProfileRuleShape;

/**
 * Provides read-only control-plane metadata for Managing field view profiles.
 */
interface AdministrationFieldViewProfileCatalogProviderInterface
{
    /** @return list<ManagingFieldViewProfileCatalogItem> */
    public function catalogItems(): array;

    /** @return list<ManagingFieldViewProfilePriorityRow> */
    public function priorityRows(): array;

    /** @return list<ManagingFieldViewProfileRuleShape> */
    public function ruleShapes(): array;
}
