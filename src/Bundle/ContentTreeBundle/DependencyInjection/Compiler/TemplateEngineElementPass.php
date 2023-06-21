<?php

namespace Trollfjord\Bundle\ContentTreeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Trollfjord\Bundle\ContentTreeBundle\TemplateEngine\TemplateEngine;
use function is_callable;

/**
 * Class TemplateEngineElementPass
 *
 * @author  Maurice Karg <karg@helliwood.com>
 *
 * @package Trollfjord\Bundle\ContentTreeBundle\DependencyInjection\Compiler
 */
class TemplateEngineElementPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // always first check if the primary service is defined
        if (! $container->has(TemplateEngine::class)) {
            return;
        }
        $definition = $container->findDefinition(TemplateEngine::class);

        // find all service IDs with the content_tree.template_engine.element tag
        $taggedServices = $container->findTaggedServiceIds('content_tree.template_engine.element');

        foreach ($taggedServices as $id => $tags) {
            // add the element to the TemplateEngine service
            if (is_callable($id . '::getPossibleChildren')) {
                foreach ($id::getPossibleChildren() as $childElement) {
                    if (! $container->has($childElement)) {
                        $container->set($childElement, new $childElement());
                        $def = new Definition($childElement);
                        $def->setShared(false);
                        $def->setPublic(true);
                        $container->setDefinition($childElement, $def);
                    }
                }
            }
            $definition->addMethodCall('addElement', [$id]);
        }
    }
}
