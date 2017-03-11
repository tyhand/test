<?php

namespace AppBundle\JsonApiResource;

use JsonApiBundle\Annotation\Resource;
use JsonApiBundle\Annotation\Attribute;
use JsonApiBundle\Annotation\HasMany;
use JsonApiBundle\Annotation\Filter;
use JsonApiBundle\Annotation\Validator;

use JsonApiBundle\JsonApiResource\JsonApiResource;
use JsonApiBundle\JsonApiResource\Resource as ApiResource;

/**
 * @Resource(entity="AppBundle\Entity\Author")
 */
class AuthorResource extends ApiResource
{
    /**
     * @Attribute
     */
    public $name;

    /**
     * @HasMany
     */
    public $books;
}
