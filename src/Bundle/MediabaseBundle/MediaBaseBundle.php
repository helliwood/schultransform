<?php

namespace Trollfjord\Bundle\MediaBaseBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class MediaBaseBundle
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\MediaBaseBundle
 */
class MediaBaseBundle extends TrollfjordBundle
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

    /**
     * @param ItemInterface $menuItem
     */
    public function extendMenu(ItemInterface $menuItem)
    {
        $menuItem->addChild('mediabase', ['label' => 'Mediendatenbank', 'route' => 'media_base_home'])
            ->setAttribute('data-icon', 'fas fa-image')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');
    }
}
