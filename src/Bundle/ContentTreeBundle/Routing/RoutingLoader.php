<?php

namespace Trollfjord\Bundle\ContentTreeBundle\Routing;

use RuntimeException;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Trollfjord\Bundle\ContentTreeBundle\Service\SiteService;
use function is_null;

/**
 * Class RoutingLoader
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\Service
 */
class RoutingLoader extends Loader
{
    /**
     * @var bool
     */
    protected bool $isLoaded = false;

    /**
     * @var SiteService
     */
    protected SiteService $siteService;

    /**
     * RoutingLoader constructor.
     * @param SiteService $siteService
     */
    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    // @codingStandardsIgnoreStart

    /**
     * @param string|object|null $resource
     * @param string|null        $type
     * @return RouteCollection
     */
    public function load($resource, string $type = null): RouteCollection
    {
        // @codingStandardsIgnoreEnd
        if (true === $this->isLoaded) {
            throw new RuntimeException('Do not add the "contenttree-routes" loader twice');
        }

        $routes = new RouteCollection();

        foreach ($this->siteService->getPublishedSites() as $site) {
            $path = $site->getUrl();
            $defaults = [
                '_controller' => ! is_null($site->getAlternativeRoute()) ? $site->getAlternativeRoute() :
                    'Trollfjord\Bundle\ContentTreeBundle\ControllerPublic\IndexController::index',
            ];
            $requirements = [
                //'parameter' => '\d+',
            ];
            $route = new Route($path, $defaults, $requirements);
            $routeName = $site->getRouteName();
            $routes->add($routeName, $route);
        }


        $this->isLoaded = true;

        return $routes;
    }

    // @codingStandardsIgnoreStart

    /**
     * @param string|object|null $resource
     * @param string|null        $type
     * @return bool
     */
    public function supports($resource, string $type = null): bool
    {
        // @codingStandardsIgnoreEnd
        return 'contenttree-routes' === $type;
    }
}
