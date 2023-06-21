<?php

namespace Trollfjord;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Core\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        if (is_file($this->getProjectDir() . '/config/services.yaml')) {
            $container->import($this->getProjectDir() . '/config/{services}.yaml');
            $container->import($this->getProjectDir() . '/config/{services}_' . $this->environment . '.yaml');
        }

        $container->import($this->getProjectDir() . '/config/{packages}/*.yaml');
        $container->import($this->getProjectDir() . '/config/{packages}/' . $this->environment . '/*.yaml');

    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        parent::configureRoutes($routes);
        $routes->import($this->getProjectDir() . '/config/{routes}/' . $this->environment . '/*.yaml');
        $routes->import($this->getProjectDir() . '/config/{routes}/*.yaml');

        //if (is_file($this->getProjectDir() . '/config/routes.yaml')) {
        //    $routes->import($this->getProjectDir() . '/config/{routes}.yaml');
        //}
    }
}
