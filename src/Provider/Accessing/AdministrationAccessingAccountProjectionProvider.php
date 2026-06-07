<?php

declare(strict_types=1);

namespace App\Administering\Provider\Accessing;

use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountProjection;

/**
 * Self-contained Administering fallback when the Accessing component is not installed.
 */
final class AdministrationAccessingAccountProjectionProvider implements AdministrationAccountProjectionProviderInterface
{
    /** @return list<AdministrationAccountProjection> */
    public function recent(int $limit = 25): array
    {
        return [];
    }

    public function findBySubjectId(string $subjectId): ?AdministrationAccountProjection
    {
        return null;
    }
}
