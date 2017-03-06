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
     *
     * @Required
     */
    public $entity;

    /**
     * Get the backing entity (or entities seperated by comma)
     * @return string Entity (or comma seperated entities)
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
