<?php

namespace Trollfjord\Bundle\GlossaryBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class GlossaryBundle
 *
 * @author  Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\GlossaryBundle
 */
class GlossaryBundle extends TrollfjordBundle
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
    public function extendMenu(ItemInterface $menuItem): void
    {
        $menuItem->addChild('glossary_', ['label' => 'Glossar', 'route' => 'glossary_home'])
            ->setAttribute('data-icon', 'fas fa-book-open')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');

    }
}
