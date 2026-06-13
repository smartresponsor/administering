<?php

declare(strict_types=1);

namespace App\Administering\Service\Http\Administration\Template;

/**
 * Skeleton HTTP service for the Administration CRUD route-map entry `administration.template.export`.
 */
final readonly class AdministrationTemplateExportService
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'routeKey' => 'administration.template.export',
            'status' => 'skeleton',
        ];
    }
}
