<?php

namespace Trollfjord\Bundle\TFSecurityBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class TFSecurityBundle
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\TFSecurityBundle
 */
class TFSecurityBundle extends TrollfjordBundle
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


    /**
     * @param ItemInterface $menuItem
     */
    public function extendMenu(ItemInterface $menuItem)
    {
        $menuItem->addChild('TFSecurityMails', ['label' => 'Spammails', 'route' => 'TFSecurity_home'])
            ->setAttribute('data-icon', 'fa-duotone fa-shield-virus')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link')
            ;;
        $menuItem->addChild('TFSecurityLogs', ['label' => 'Logfiles', 'route' => 'TFSecurity_erroroverview'])
            ->setAttribute('data-icon', 'fa-duotone fa-bug')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link')
        ;;
    }
}
