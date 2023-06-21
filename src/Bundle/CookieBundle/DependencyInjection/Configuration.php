<?php

namespace Trollfjord\Bundle\CookieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\CookieBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cookie');


        return $treeBuilder;
    }
}
