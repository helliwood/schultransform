<?php

namespace Trollfjord\Bundle\ContentTreeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('content_tree');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('template_path')
            ->defaultValue('%kernel.project_dir%/snippets')
            ->end()/*
                ->arrayNode('menu')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('route')
                            ->defaultValue('content_tree')
                        ->end()
                    ->end()
                ->end()*/
            ->end();

        return $treeBuilder;
    }
}
