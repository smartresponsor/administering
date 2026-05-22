<?php

declare(strict_types=1);

namespace App\Administering\ServiceInterface\Accessing;

use App\Administering\Value\Accessing\AdministrationAccountProjection;

/**
 * Provides Accessing-owned account projections for Administering screens.
 */
interface AdministrationAccountProjectionProviderInterface
{
    /** @return list<AdministrationAccountProjection> */
    public function recent(int $limit = 25): array;

    public function findBySubjectId(string $subjectId): ?AdministrationAccountProjection;
}
