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
     * Hash of attributes keyed by json name
     * @var array
     */
    private $attributes = [];

    /**
     * Hash of relationships keyed by json name
     * @var array
     */
    private $relationships = [];

    /**
     * Filters
     * @var array
     */
    private $filters = [];

    /**
     * Validators
     * @var array
     */
    private $validators = [];

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
        $json = [
            'type' => $this->getName(),
            'id'   => $this->getIdForEntity($entity)
        ];

        if (0 < count($this->getAttributes())) {
            $json['attributes'] = [];
        }
        if (0 < count($this->getRelationships())) {
            $json['relationships'] = [];
        }

        foreach($this->getAttributes() as $attribute) {
            $json = $attribute->addToJson($entity, $json);
        }

        foreach($this->getRelationships() as $relationship) {
            $json = $relationship->addToJson($entity, $json);
        }

        return $json;
    }

    /**
     * Hydrate entities from data
     * @param  array  $data Hash from json data
     * @return mixed        Entity
     */
    public function toEntity(array $data)
    {
        // Type check
        if ($data['type'] !== $this->getName()) {
            throw new \Exception('Resource and type do not match');
        }

        // Check if an id is present
        if (array_key_exists('id', $data)) {
            // Load the entity
            $entity = $this->loadEntityById($data['id']);
        } else {
            // Create new
            $entity = $this->createNewEntity();
        }

        if (array_key_exists('attributes', $data)) {
            foreach($data['attributes'] as $name => $value) {
                $attribute = $this->getAttributeByJsonName($name);
                if (null !== $attribute) {
                    $entity = $attribute->addToEntity($entity, $value);
                }
            }
        }

        if (array_key_exists('relationships', $data)) {
            foreach($data['relationships'] as $name => $relationData) {
                $relationship = $this->getRelationshipByJsonName($name);
                if (null !== $relationship) {
                    $relationship->addToEntity($entity, $relationData, $this->getManager());
                }
            }
        }

        return $entity;
    }

    /////////////////////
    // PROCESS METHODS //
    /////////////////////

    /**
     * Get the id from an entity or entity collection
     * @param  mixed  $entity Entity
     * @return string         Id
     */
    protected function getIdForEntity($entity)
    {
        if ($this->isComposite()) {
            $ids = [];
            foreach($entity as $e) {
                $ids[] = $e->getId();
            }
            return implode('-', $ids);
        } else {
            return $entity->getId();
        }
    }

    /**
     * Load the resource's entity (or entities) by id
     * @param  mixed $id Id
     * @return mixed     Entity
     */
    protected function loadEntityById($id)
    {
        if ($this->isComposite()) {
            $ids = explode('-', $id);
            $entityMap = [];
            foreach($this->getEntity() as $key => $entityName) {
                $entityMap[$entityName] = $this->getManager()->getEntityLoader()->loadEntity($entityName, $ids[$key]);
            }
            return $entityMap;
        } else {
            return $this->getManager()->getEntityLoader()->loadEntity($this->getEntity(), $id);
        }
    }

    /**
     * Create a new entity for this resource
     * @return mixed Entity
     */
    protected function createNewEntity()
    {
        if ($this->isComposite()) {
            $entityMap = [];
            foreach($this->getEntity() as $class) {
                $entityMap[$class] = new $class();
            }
            return $entityMap;
        } else {
            return new $this->entity();
        }
    }

    ////////////////////////////////////
    // FILTERING, SORTING, AND PAGING //
    ////////////////////////////////////


    public function find()
    {
        // Start
        // Filters
        // Sorts
        // Pagination
        // beforeQuery
        // After find
    }

    public function processFilters()
    {

    }


    public function processSorts()
    {

    }


    public function processPagination()
    {

    }


    public function beforeQuery()
    {

    }

    public function afterFind()
    {

    }

    ////////////////
    // VALIDATION //
    ////////////////

    /**
     * Validate the entity generated from the json input
     * @return array Errors if any
     */
    public function validateEntity($entity)
    {
        $errors = [];
        foreach($this->validators as $validator) {
            if (!$this->{$validator->getMethod()}($entity)) {
                $errors[] = new Error(
                    $validator->getErrorTitle(),
                    $validator->getErrorDetail(),
                    $valdiator->getErrorCode()
                );
            }
        }
        $errors = $this->afterValidation($errors);
        return $errors;
    }

    /**
     * Stub method to perform operations on the errors after the fact
     * @param  array $errors Error objects (or empty if there are none)
     * @return array         Altered errors array
     */
    public function afterValidation($errors)
    {
        return $errors;
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
        $this->attributes[$attribute->getJsonName()] = $attribute;
        return $this;
    }

    /**
     * Add a relationship to the resource
     * @param  Relationship $relation Relation
     * @return self
     */
    public function addRelationship(Relationship $relation)
    {
        $this->relationships[$relation->getJsonName()] = $relation;
        return $this;
    }

    /**
     * Add a filter
     * @param Filter $filter Filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[$filter->getName()] = $filter;
        return $this;
    }

    /**
     * Add a validator
     * @param Validator $validator Validator
     */
    public function addValidator(Validator $validator)
    {
        $this->validators[$validator->getMethod()] = $validator;
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

    /**
     * Get the attributes
     * @return array Array of attributes keyed by json name
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get an attribute by its json name
     * @param  string    $jsonName Json name
     * @return Attribute           Attribute if exists
     */
    public function getAttributeByJsonName($jsonName)
    {
        if (array_key_exists($jsonName, $this->attributes)) {
            return $this->attributes[$jsonName];
        } else {
            return null;
        }
    }

    /**
     * Get the relationships
     * @return array Relationship hash keyed by json name
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Get a relationhsip by its json name
     * @param  string       $jsonName Json name
     * @return Relationship           Relationship if exists
     */
    public function getRelationshipByJsonName($jsonName)
    {
        if (array_key_exists($jsonName, $this->relationships)) {
            return $this->relationships[$jsonName];
        } else {
            return null;
        }
    }
}
