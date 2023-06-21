<?php

namespace Trollfjord\Bundle\ContentTreeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class ContentTreeExtension
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\DependencyInjection
 */
class ContentTreeExtension extends Extension
{

    /**
     * @param string[]         $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
        //$loader->load('doctrine.yaml');

        $container->setParameter('content_tree.template_path', $config["template_path"]);
        //$loader->load('routing.yaml');

        /*

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);*/
    }
}
