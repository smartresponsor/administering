<?php

declare(strict_types=1);

namespace App\Administering\Service\Http\Administration\Template;

/**
 * Skeleton HTTP service for the Administration CRUD route-map entry `administration.template.create`.
 */
final readonly class AdministrationTemplateCreateService
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'routeKey' => 'administration.template.create',
            'status' => 'skeleton',
        ];
    }
}
