<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Operation;

use App\Administering\Value\Admin\AdministrationServiceSectionAnchorSyncResult;

/**
 * Runs service-section anchor synchronization as a metadata-only operation.
 */
interface AdministrationServiceSectionAnchorSyncOperationServiceInterface
{
    /** @return list<AdministrationServiceSectionAnchorSyncResult> */
    public function synchronize(?string $section = null): array;

    /** @return list<string> */
    public function supportedSections(): array;
}
