<?php

namespace JsonApiBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class HasOne extends Annotation
{
    /**
     * Resource of the many side of the relation
     * @var string
     *
     * @Required
     */
    public $resource;

    /**
     * Property name
     * @var string
     */
    public $property;

    /**
     * Entity whose property is being pointed at if the resource is a composite
     * @var string
     */
    public $entity;

    /**
     * Method to call on the entity to get the id
     * @var string
     */
    public $getIdMethod = 'getId';

    /**
     * Get the value of Resource of the many side of the relation
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the value of Property name
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Get the value of Entity whose property is being pointed at if the resource is a composite
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get the getid method for the relationship
     * @return string
     */
    public function getGetIdMethod()
    {
        return $this->getIdMethod;
    }
}
