<?php

namespace AppBundle\JsonApiResource;

use JsonApiBundle\Annotation\Resource;
use JsonApiBundle\Annotation\Attribute;

use JsonApiBundle\JsonApiResource\JsonApiResource;
use JsonApiBundle\JsonApiResource\Resource as ApiResource;

/**
 * @Resource
 */
class UserResource extends ApiResource
{
    /**
     * @Attribute
     */
    public $username;
}
