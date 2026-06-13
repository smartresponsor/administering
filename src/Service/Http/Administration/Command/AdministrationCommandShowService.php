<?php

declare(strict_types=1);

namespace App\Administering\Service\Http\Administration\Command;

/**
 * Skeleton HTTP service for the Administration CRUD route-map entry `administration.command.show_id`.
 */
final readonly class AdministrationCommandShowService
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'routeKey' => 'administration.command.show_id',
            'status' => 'skeleton',
        ];
    }
}
