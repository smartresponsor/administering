<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Admin;

use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;

/**
 * Synchronizes one service-section primary CRUD anchor table from its source provider.
 */
interface AdministrationServiceSectionAnchorSyncServiceInterface
{
    public function sectionKey(): string;

    public function synchronize(): AdministrationServiceSectionAnchorSyncResult;
}
