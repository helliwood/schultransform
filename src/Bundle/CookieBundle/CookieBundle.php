<?php

namespace Trollfjord\Bundle\CookieBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Bundle\CookieBundle\Service\RegexServiceInterface;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class CookieBundle
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\CookieBundle
 */
class CookieBundle extends TrollfjordBundle
{

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
     * @return ImportConfigurator|null
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
        $menuItem->addChild('cookie_', ['label' => 'Cookie-Banner', 'route' => 'cookie_home'])
            ->setAttribute('data-icon', 'fas fa-cookie')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');

    }
}
