<?php

namespace Trollfjord\Bundle\MediaBaseBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class MediaBaseExtension
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\MediaBaseBundle\DependencyInjection
 */
class MediaBaseExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
        //$loader->load('doctrine.yaml');

        $container->setParameter('media_base.storage_service', $config["storage_service"]);
        $container->setParameter('media_base.storage_service_cache', $config["storage_service_cache"]);
        $container->setParameter('media_base.file_path', $config["file_path"]);
        $container->setParameter('media_base.file_path_cache', $config["file_path_cache"]);
        $container->setParameter('media_base.mimeTypes', $config["mimeTypes"]);
        //$loader->load('routing.yaml');

        /*

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);*/
    }
}
