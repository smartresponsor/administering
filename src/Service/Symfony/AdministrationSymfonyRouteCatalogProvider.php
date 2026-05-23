<?php

declare(strict_types=1);

namespace App\Administering\Service\Symfony;

use App\Administering\ServiceInterface\Symfony\AdministrationSymfonyRouteCatalogProviderInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides Symfony route metadata for the Symfony administration section.
 */
final readonly class AdministrationSymfonyRouteCatalogProvider implements AdministrationSymfonyRouteCatalogProviderInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function routes(): array
    {
        $rows = [];
        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            $methods = $route->getMethods();
            $rows[] = [
                'route' => $name,
                'path' => $route->getPath(),
                'methods' => [] !== $methods ? array_values($methods) : ['ANY'],
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a['route'] <=> $b['route']);

        return $rows;
    }
}
