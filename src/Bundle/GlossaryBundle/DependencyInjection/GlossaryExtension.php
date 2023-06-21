<?php

namespace Trollfjord\Bundle\GlossaryBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class GlossaryExtension
 *
 * @author Juan Mayoral <mayoral@helliwood.com>
 *
 * @package Trollfjord\Bundle\GlossaryBundle\DependencyInjection
 */
class GlossaryExtension extends Extension
{

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container):void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');

        $container->setParameter('glossary.items_per_page_front', $config["items_per_page_front"]);
        $container->setParameter('glossary.items_per_page', $config["items_per_page"]);
        $container->setParameter('glossary.index_template', $config["index_template"]);
        $container->setParameter('glossary.twig_template_regex', $config["regex_template"]);
        $definition = $container->getDefinition('glossary_service');
        if (null !== $config['service_regex']) {
            $definition->setArgument(0, new Reference($config['service_regex']));
        }
    }
}
