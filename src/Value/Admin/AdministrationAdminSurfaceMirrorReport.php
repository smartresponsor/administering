<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Report for the admin-surface route/menu/service mirror guard.
 */
final readonly class AdministrationAdminSurfaceMirrorReport
{
    /**
     * @param list<array<string, string|bool>>                                                   $routes
     * @param list<array{severity:string,code:string,message:string,path?:string,route?:string}> $issues
     */
    public function __construct(
        public array $routes,
        public array $issues,
    ) {
    }

    public function errorCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'error' === ($issue['severity'] ?? null)));
    }

    public function warningCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'warning' === ($issue['severity'] ?? null)));
    }

    public function isClean(): bool
    {
        return 0 === $this->errorCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.admin_surface_mirror.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'routeCount' => count($this->routes),
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'routes' => $this->routes,
            'issues' => $this->issues,
        ];
    }
}
