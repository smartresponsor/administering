<?php

declare(strict_types=1);

namespace App\Administering\Scanner\Admin;

use App\Administering\Provider\Admin\AdministrationRuntimeSourceNavigationProvider;
use App\Administering\Value\Admin\AdministrationRuntimeScopeSourceMirrorReport;

/**
 * Static guard that keeps runtime-scope source index routes service-backed.
 * Runtime-source pages may stay secondary and do not have to appear in the
 * primary EasyAdmin menu.
 */
final readonly class AdministrationRuntimeScopeSourceMirrorScanner
{
    public function __construct(
        private AdministrationRuntimeSourceNavigationProvider $runtimeSourceNavigationProvider,
    ) {
    }

    public function scan(string $projectDir): AdministrationRuntimeScopeSourceMirrorReport
    {
        $controllerFile = rtrim($projectDir, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Controller'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'RuntimeScope'.DIRECTORY_SEPARATOR.'AdministrationRuntimeScopeSourceIndexController.php';
        $menuBuilderFile = rtrim($projectDir, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Builder'.DIRECTORY_SEPARATOR.'Admin'.DIRECTORY_SEPARATOR.'AdministrationMainMenuBuilder.php';

        $issues = [];
        $routes = [];

        if (!is_file($controllerFile)) {
            return new AdministrationRuntimeScopeSourceMirrorReport([], [[
                'severity' => 'error',
                'code' => 'missing_controller',
                'message' => 'Administration runtime-scope source index controller is missing.',
                'path' => $controllerFile,
            ]]);
        }

        if (!is_file($menuBuilderFile)) {
            return new AdministrationRuntimeScopeSourceMirrorReport([], [[
                'severity' => 'error',
                'code' => 'missing_menu_builder',
                'message' => 'Administration main menu builder is missing.',
                'path' => $menuBuilderFile,
            ]]);
        }

        $controllerSource = (string) file_get_contents($controllerFile);
        $menuSource = (string) file_get_contents($menuBuilderFile);
        $expectedRoutes = array_map(
            static fn ($item): string => $item->routeName,
            $this->runtimeSourceNavigationProvider->items(),
        );

        foreach ($this->extractRoutes($controllerSource) as $route) {
            $routeName = $route['name'];
            $actionSource = $this->extractActionSource($controllerSource, $route['method']);
            $hasServiceCall = (bool) preg_match('/[A-Za-z0-9_]+IndexService->index\(/', $actionSource);
            $hasMenuMirror = in_array($routeName, $expectedRoutes, true);

            $routes[] = [
                'route' => $routeName,
                'path' => $route['path'],
                'method' => $route['method'],
                'serviceBacked' => $hasServiceCall,
                'easyAdminMenuMirrored' => $hasMenuMirror,
            ];

            if (!str_starts_with($route['path'], '/admin/')) {
                $issues[] = [
                    'severity' => 'error',
                    'code' => 'non_admin_surface_route',
                    'message' => 'Administering custom runtime-scope route must stay under /admin/ and must not become a frontend route.',
                    'path' => $route['path'],
                    'route' => $routeName,
                ];
            }

            if (!$hasServiceCall) {
                $issues[] = [
                    'severity' => 'error',
                    'code' => 'missing_service_entry',
                    'message' => 'Runtime-scope source index action must call a paired index service instead of owning business logic.',
                    'path' => $route['path'],
                    'route' => $routeName,
                ];
            }
        }

        foreach ($expectedRoutes as $expectedRoute) {
            if (!str_contains($controllerSource, "name: '$expectedRoute'")) {
                $issues[] = [
                    'severity' => 'error',
                    'code' => 'navigation_route_missing_controller',
                    'message' => 'Runtime source navigation item points to a route that is not declared by the runtime-scope source controller.',
                    'route' => $expectedRoute,
                ];
            }
        }

        return new AdministrationRuntimeScopeSourceMirrorReport($routes, $issues);
    }

    /** @return list<array{path:string,name:string,method:string}> */
    private function extractRoutes(string $source): array
    {
        $routes = [];
        $pattern = '/#\[Route\(\'([^\']+)\',\s*name:\s*\'([^\']+)\'.*?\)\]\s*public function ([A-Za-z0-9_]+)\(/s';

        if (!preg_match_all($pattern, $source, $matches, PREG_SET_ORDER)) {
            return [];
        }

        foreach ($matches as $match) {
            if (!str_contains($match[1], '/index')) {
                continue;
            }

            $routes[] = [
                'path' => $match[1],
                'name' => $match[2],
                'method' => $match[3],
            ];
        }

        return $routes;
    }

    private function extractActionSource(string $source, string $method): string
    {
        $position = strpos($source, 'public function '.$method.'(');
        if (false === $position) {
            return '';
        }

        $nextPosition = strpos($source, 'public function ', $position + 1);
        if (false === $nextPosition) {
            return substr($source, $position);
        }

        return substr($source, $position, $nextPosition - $position);
    }
}
