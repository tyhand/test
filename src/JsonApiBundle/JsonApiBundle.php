<?php

namespace JsonApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use JsonApiBundle\DependencyInjection\Compiler\FormatterPass;

class JsonApiBundle extends Bundle
{
    /**
     * @{inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new FormatterPass());
    }
}
