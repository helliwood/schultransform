<?php

namespace Trollfjord\Bundle\PublicUserBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class PublicUserBundle
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\PublicUserBundle
 */
class PublicUserBundle extends TrollfjordBundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }

    /**
     * @param RoutingConfigurator $routes
     * @return ImportConfigurator
     */
    public function configureRoutes(RoutingConfigurator $routes): ImportConfigurator
    {
        return $routes->import(__DIR__ . '/Resources/config/routing.yaml');
    }

    /**
     * @param RoutingConfigurator $routes
     * @return ImportConfigurator
     */
    public function configurePublicRoutes(RoutingConfigurator $routes): ?ImportConfigurator
    {
        return $routes->import(__DIR__ . '/Resources/config/routing_public.yaml');
    }

    public function extendMenu(ItemInterface $menuItem)
    {
        $menuItem->addChild('publicuser', ['label' => 'Public Users', 'route' => 'public_user_home'])
            ->setAttribute('data-icon', 'fad fa-users')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');

        $menuItem->addChild('schooloverview', ['label' => 'Schulen', 'route' => 'school_overview'])
            ->setAttribute('data-icon', 'fad fa-school')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');
    }
}
