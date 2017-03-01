<?php

namespace JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class FormatterPass implements CompilerPassInterface
{
    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Check that the resource manager is defined
        if (!$container->has('jsonapi.resource_manager')) {
            return;
        }

        // Get the manager definition
        $definition = $container->findDefinition('jsonapi.resource_manager');

        // Get all the services tagged with the formatter tag
        $tagged = $container->findTaggedServiceIds('jsonapi.formatter');
        foreach($tagged as $id => $tag) {
            // Add to the manager
            $definition->addMethodCall('addFormatter', [new Reference($id)]);
        }
    }
}
