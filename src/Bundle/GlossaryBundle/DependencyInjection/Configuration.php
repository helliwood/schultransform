<?php

namespace Trollfjord\Bundle\GlossaryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\GlossaryBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('glossary');
        $treeBuilder->getRootNode()
            ->children()
            ->integerNode('items_per_page')
            ->min(2)
            ->info('number of items to be displayed in the word list in the backend')
            ->defaultValue(5)->end()
            ->integerNode('items_per_page_front')
            ->min(2)
            ->info('number of items to be displayed in the word list in the frontend')
            ->defaultValue(5)->end()
            ->scalarNode('index_template')
            ->info('This value is to define the template that will be used to display the glossary on the home page')
            ->defaultValue('@Glossary/public/index.html.twig')->end()
            ->scalarNode('service_regex')
            ->info('This value is to define the regex service, which is in charge of exchanging the words stored in the glossary table and the words contained in the page content.')
            ->defaultNull()->end()
            ->scalarNode('regex_template')
            ->info('There is a small template that shows the words that were found in the content and surrounds them with an html link, with this configuration you can determine another template for this task.')
            ->defaultValue('@Glossary/public/glossary-link-simple.html.twig')->end()
            ->end();

        return $treeBuilder;
    }
}
