<?php

namespace AppBundle\JsonApiResource;

use JsonApiBundle\Annotation\Resource;
use JsonApiBundle\Annotation\Attribute;

use JsonApiBundle\JsonApiResource\JsonApiResource;
use JsonApiBundle\JsonApiResource\Resource as ApiResource;

/**
 * @Resource(entity="AppBundle\Entity\Foo")
 */
class FooResource extends ApiResource
{
    /**
     * @Attribute
     */
    public $name;

    /**
     * @Attribute
     */
    public $secretNumber;
}
