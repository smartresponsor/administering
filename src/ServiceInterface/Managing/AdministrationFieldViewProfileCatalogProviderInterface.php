<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Managing;

use App\Administering\Value\Rolling\AdministrationFieldViewProfileCatalogItem;
use App\Administering\Value\Rolling\AdministrationFieldViewProfilePriorityRow;
use App\Administering\Value\Rolling\AdministrationFieldViewProfileRuleShape;

/**
 * Provides read-only control-plane metadata for Managing field view profiles.
 */
interface AdministrationFieldViewProfileCatalogProviderInterface
{
    /** @return list<AdministrationFieldViewProfileCatalogItem> */
    public function catalogItems(): array;

    /** @return list<AdministrationFieldViewProfilePriorityRow> */
    public function priorityRows(): array;

    /** @return list<AdministrationFieldViewProfileRuleShape> */
    public function ruleShapes(): array;
}
