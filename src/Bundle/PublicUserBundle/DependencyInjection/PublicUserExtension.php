<?php

namespace Trollfjord\Bundle\PublicUserBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class PublicUserExtension
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\PublicUserBundle\DependencyInjection
 */
class PublicUserExtension extends Extension
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
        //$loader->load('security.yaml');

        $container->setParameter('public_user.security_logout', $config["security_logout"]);
        $container->setParameter('public_user.security_form_login', $config["security_form_login"]);
        $container->setParameter('public_user.login_route', $config["login_route"]);
        $container->setParameter('public_user.login_success_route', $config["login_success_route"]);
        $container->setParameter('public_user.logout_route', $config["logout_route"]);

    }
}
