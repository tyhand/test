<?php

namespace JsonApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Resource extends Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $entity;

    /**
     * Get the name
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the backing entity (or entities seperated by comma)
     * @return string Entity (or comma seperated entities)
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
