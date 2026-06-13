<?php

declare(strict_types=1);

namespace App\Administering\Service\Http\Administration\Form;

/**
 * Skeleton HTTP service for the Administration CRUD route-map entry `administration.form.bulk`.
 */
final readonly class AdministrationFormBulkService
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'routeKey' => 'administration.form.bulk',
            'status' => 'skeleton',
        ];
    }
}
