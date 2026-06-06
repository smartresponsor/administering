<?php

declare(strict_types=1);

namespace App\Administering;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Kernel\BundleInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';

        foreach ($contents as $class => $environments) {
            if (($environments[$this->environment] ?? false) || ($environments['all'] ?? false)) {
                $bundle = new $class();
                if (!$bundle instanceof BundleInterface) {
                    continue;
                }

                yield $bundle;
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/packages/*.yaml');
        $container->import('../config/packages/'.$this->environment.'/*.yaml', null, true);
        $container->import('../config/services.yaml');
        $container->import('../config/services_'.$this->environment.'.yaml', null, true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('../config/routes/*.yaml');
        $routes->import('../config/routes/'.$this->environment.'/*.yaml', null, true);
    }
}
