<?php

namespace JsonApiBundle\JsonApiResource;

use Doctrine\ORM\EntityManager;

abstract class Resource
{
    ////////////////
    // PROPERTIES //
    ////////////////

    /**
     * Resource Manager that is managing this resource
     * @var ResourceManager
     */
    private $manager;

    /**
     * Name of the backing entity (or entities seperated by comma if composite)
     * @var string
     */
    private $entity;

    /**
     * Name of the resource
     * @var string
     */
    private $name;

    /**
     * True if the resource is represented by multiple backing entities
     * @var boolean
     */
    private $isComposite;

    /**
     * Hash of attributes
     * @var array
     */
    private $attributes = [];

    /**
     * Hash of attributes keyed by json name for hydration
     * @var array
     */
    private $attributesByJsonName = [];

    ////////////////////
    // FORMAT METHODS //
    ////////////////////

    /**
     * Convert the given entity to json
     * @param  mixed $entity Entity or entities to convert
     * @return array         Hash for conversion to json
     */
    public function toJson($entity)
    {
        return ['data' => []];
    }


    public function toEntity(array $data)
    {

    }

    /////////////////
    // ADD METHODS //
    /////////////////

    /**
     * Add attribute to the resource
     * @param  Attribute $attribute Attribute
     * @return self
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[$attribute->getName()] = $attribute;
        $this->attributesByJsonName[$attribute->getJsonName()] = $attribute;
        return $this;
    }

    /////////////////////////
    // GETTERS AND SETTERS //
    /////////////////////////

    /**
     * Set the resource manager
     * @param  ResourceManager $manager Resource Manager
     * @return self
     */
    public function setManager(ResourceManager $manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * Get the manager
     * @return ResourceManager Resource Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Get the value of Name of the backing entity (or entities seperated by comma if composite)
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set the value of Name of the backing entity (or entities seperated by comma if composite)
     * @param string entity
     * @return self
     */
    public function setEntity($entity)
    {
        if (false === strpos($entity, ',')) {
            $this->isComposite = false;
            $this->entity = $entity;
        } else {
            $this->isComposite = true;
            $this->entity = explode(',', $entity);
        }
        return $this;
    }

    /**
     * Get the value of Name of the resource
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of Name of the resource
     * @param string name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the value of True if the resource is represented by multiple backing entities
     * @return boolean
     */
    public function isComposite()
    {
        return $this->isComposite;
    }
}
