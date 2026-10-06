<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileCatalogItem;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfilePriorityRow;
use App\Administering\Value\Managing\AdministrationManagingFieldViewProfileRuleShape;

/**
 * Provides read-only control-plane metadata for Managing field view profiles.
 */
interface AdministrationFieldViewProfileCatalogProviderInterface
{
    /** @return list<AdministrationManagingFieldViewProfileCatalogItem> */
    public function catalogItems(): array;

    /** @return list<AdministrationManagingFieldViewProfilePriorityRow> */
    public function priorityRows(): array;

    /** @return list<AdministrationManagingFieldViewProfileRuleShape> */
    public function ruleShapes(): array;
}
