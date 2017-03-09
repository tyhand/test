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
     * Allow the resource to be deleted
     * @var boolean
     */
    public $allowDelete = true;

    /**
     * Run the symfony validator during the validation phase
     * @var boolean
     */
    public $runSymfonyValidator = true;

    /**
     * Get the backing entity (or entities seperated by comma)
     * @return string Entity (or comma seperated entities)
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Allow delete
     * @return boolean
     */
    public function getAllowDelete()
    {
        return $this->allowDelete;
    }

    /**
     * Whether to run the symfony validator
     * @return boolean
     */
    public function getRunSymfonyValidator()
    {
        return $this->runSymfonyValidator;
    }
}
