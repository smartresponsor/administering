<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Managing\Value\Administration\ManagingFieldViewProfileCatalogItem;
use App\Managing\Value\Administration\ManagingFieldViewProfilePriorityRow;
use App\Managing\Value\Administration\ManagingFieldViewProfileRuleShape;

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
