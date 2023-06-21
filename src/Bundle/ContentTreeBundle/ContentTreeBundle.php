<?php

namespace Trollfjord\Bundle\ContentTreeBundle;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Loader\Configurator\ImportConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Trollfjord\Bundle\ContentTreeBundle\DependencyInjection\Compiler\TemplateEngineElementPass;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\PreRenderInterface;
use Trollfjord\Core\TrollfjordBundle;

/**
 * Class ContentTreeBundle
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle
 */
class ContentTreeBundle extends TrollfjordBundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new TemplateEngineElementPass());
        $container->registerForAutoconfiguration(PreRenderInterface::class)
            ->addTag('content_tree.template_engine.pre_renderer');
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
        $menuItem->addChild('content_tree', ['label' => 'Content-Tree', 'route' => 'content_tree_home'])
            ->setAttribute('data-icon', 'fas fa-list')
            ->setAttribute('class', 'w-100')
            ->setLinkAttribute('class', 'nav-link');
    }
}
