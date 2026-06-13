<?php

declare(strict_types=1);

namespace App\Administering\Service\Http\Administration\Form;

/**
 * Skeleton HTTP service for the Administration CRUD route-map entry `administration.form.create`.
 */
final readonly class AdministrationFormCreateService
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'routeKey' => 'administration.form.create',
            'status' => 'skeleton',
        ];
    }
}
