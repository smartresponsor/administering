<?php

declare(strict_types=1);

namespace App\Administering\Provider\Accessing;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationProjection;
use App\Administering\ServiceInterface\Accessing\AdministrationAccountProjectionProviderInterface;
use App\Administering\Value\Accessing\AdministrationAccountProjection;

/**
 * Reads safe account projections from Accessing for Administering visualization.
 */
final class AccessingAdministrationAccountProjectionProvider implements AdministrationAccountProjectionProviderInterface
{
    public function __construct(private readonly AccessAccountAdministrationProjectionProviderInterface $accessingProjectionProvider)
    {
    }

    /** @return list<AdministrationAccountProjection> */
    public function recent(int $limit = 25): array
    {
        return array_map(
            $this->map(...),
            $this->accessingProjectionProvider->recent($limit),
        );
    }

    public function findBySubjectId(string $subjectId): ?AdministrationAccountProjection
    {
        $projection = $this->accessingProjectionProvider->findBySubjectId($subjectId);

        return $projection instanceof AccessAccountAdministrationProjection ? $this->map($projection) : null;
    }

    private function map(AccessAccountAdministrationProjection $projection): AdministrationAccountProjection
    {
        return new AdministrationAccountProjection(
            $projection->subjectId(),
            $projection->identifier(),
            $projection->active(),
            $projection->verified(),
            $projection->bootstrapRoles(),
            $projection->displayName(),
        );
    }
}
