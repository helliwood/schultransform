<?php

namespace Trollfjord\Bundle\QuestionnaireBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class QuestionnaireBundle
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\QuestionnaireBundle
 */
class QuestionnaireBundle extends TrollfjordBundle
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
        $menuItem->addChild('questionnaire', ['label' => 'Fragebögen', 'route' => 'questionnaire_home'])
            ->setAttribute('data-icon', 'fas fa-clipboard-check')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link')
            ->addChild('recommendation', [
                'label' => 'Handlungsempfehlungen',
                'route' => 'questionnaire_recommendation_home',
                'routeParameters' => [],
            ]);;
    }
}
