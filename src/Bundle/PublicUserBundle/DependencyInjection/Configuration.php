<?php

namespace Trollfjord\Bundle\PublicUserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @author Tim Wettstein <wettstein@helliwood.com>
 *
 * @package Trollfjord\Bundle\PublicUserBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('public_user');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('login_route')
                    ->defaultValue('public_user_public_home')
                ->end()
                ->scalarNode('login_success_route')
                    ->defaultValue('public_user_public_success')
                ->end()
                ->scalarNode('logout_route')
                    ->defaultValue('public_user_public_logout')
                ->end()
                ->arrayNode("security_logout")
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        "path" => "/PublicUser/logout",
                        "target" => "/PublicUser"
                    ])
                ->end()->arrayNode("security_form_login")
                    ->scalarPrototype()->end()
                    ->defaultValue([
                        "login_path" => "public_user_public_home",
                        "check_path"=> "public_user_public_home",
                        "default_target_path" => "public_user_public_home",
                        "always_use_default_target_path" => true
                    ])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
