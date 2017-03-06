<?php

namespace JsonApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use JsonApiBundle\DependencyInjection\Compiler\FormatterPass;
use JsonApiBundle\DependencyInjection\Compiler\ResourcePass;

class JsonApiBundle extends Bundle
{
    /**
     * @{inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FormatterPass());
        $container->addCompilerPass(new ResourcePass());
    }
}
