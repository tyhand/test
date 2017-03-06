<?php

namespace JsonApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class ResourcePass implements CompilerPassInterface
{
    /**
     * @{inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Check that the resource reader is defined
        if (!$container->has('jsonapi.resource_reader')) {
            return;
        }

        // Get the manager definition
        $definition = $container->findDefinition('jsonapi.resource_reader');

        // Get all the services tagged with the resource tag
        $tagged = $container->findTaggedServiceIds('jsonapi.resource');
        foreach($tagged as $id => $tag) {
            // Add to the manager
            $definition->addMethodCall('addResource', [new Reference($id)]);
        }
    }
}
