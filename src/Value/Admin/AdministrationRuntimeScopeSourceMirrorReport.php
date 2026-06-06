<?php

declare(strict_types=1);

namespace App\Administering\Value\Admin;

/**
 * Report for the runtime-scope source route/menu/service mirror guard.
 */
final readonly class AdministrationRuntimeScopeSourceMirrorReport
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
        return count(array_filter($this->issues, static fn (array $issue): bool => 'error' === $issue['severity']));
    }

    public function warningCount(): int
    {
        return count(array_filter($this->issues, static fn (array $issue): bool => 'warning' === $issue['severity']));
    }

    public function isClean(): bool
    {
        return 0 === $this->errorCount();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema' => 'smart-responsor.administering.runtime_scope_source_mirror.v1',
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'routeCount' => count($this->routes),
            'errorCount' => $this->errorCount(),
            'warningCount' => $this->warningCount(),
            'routes' => $this->routes,
            'issues' => $this->issues,
        ];
    }
}
