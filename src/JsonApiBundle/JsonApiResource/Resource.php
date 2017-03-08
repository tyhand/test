<?php

namespace JsonApiBundle\JsonApiResource;

use Symfony\Component\HttpFoundation\ParameterBag;
use JsonApiBundle\Util\Inflect;

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

    /**
     * Whether the resource can be deleted
     * @var boolean
     */
    private $allowDelete;

    //////////
    // NAME //
    //////////

    /**
     * Get the name of the resource
     * @return string Resource name
     */
    public function getName()
    {
        preg_match('/(\w+)Resource$/', get_class($this), $matches);
        if (isset($matches[1])) {
            return Inflect::pluralize(strtolower($matches[1]));
        } else {
            throw new \Exception('Cannot determine resource name');
        }
    }

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
            if (!$attribute->getInputOnly()) {
                $json = $attribute->addToJson($entity, $json);
            }
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
    public function loadEntityById($id)
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
    public function createNewEntity()
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

    /**
     * Build a query to retrieve and sort resources
     * @param  ParameterBag $parameters Query Parameters
     * @return array                    Results
     */
    public function find(ParameterBag $parameters)
    {
        // Start
        $alias = Inflect::singularize($this->getName());
        if ($this->isComposite()) {
            // Terrible temp solution
            $queryBuilder = $this->getManager()->getEntityLoader()->getEntityManager()->getRepository(reset($this->entity))->createQueryBuilder($alias);
        } else {
            $queryBuilder = $this->getManager()->getEntityLoader()->getEntityManager()->getRepository($this->entity)->createQueryBuilder($alias);
        }

        $joinManager = new JoinManager($alias, $this, $queryBuilder, $this->getManager());

        // Filters
        if ($parameters->has('filter')) {
            $queryBuilder = $this->processFilters($parameters->get('filter'), $alias, $queryBuilder, $joinManager);
        }

        // Get the count before pagination
        $result = new FindResult();
        $totalQueryBuilder = clone $queryBuilder;
        $totalQueryBuilder->select('count(distinct ' . $alias . '.id)');
        $result->setCount($totalQueryBuilder->getQuery()->getSingleScalarResult());
        unset($totalQueryBuilder);

        // Sorts
        if ($parameters->has('sort')) {
            $queryBuilder = $this->processSorts($parameters->get('sort'), $alias, $queryBuilder, $joinManager);
        }

        // Pagination
        $queryBuilder = $this->processPagination($parameters->get('page', []), $alias, $queryBuilder, $result);

        // beforeQuery
        $queryBuilder = $this->beforeQuery($queryBuilder);

        // run the query
        $result->setResults($queryBuilder->getQuery()->getResult());

        // After find
        return $this->afterFind($result);
    }

    /**
     * Process the filters
     * @param  array        $filterParameters Filter parameters
     * @param  string       $alias            Alias of the root entity
     * @param  QueryBuilder $queryBuilder     Query Builder
     * @param  JoinManager  $joinManager      Array of joins that have been add to the querybuilder
     * @return QueryBuilder                   Altered Query Builder
     */
    public function processFilters($filterParameters, $alias, $queryBuilder, JoinManager $joinManager)
    {
        if (is_array($filterParameters)) {
            foreach($filterParameters as $name => $value) {
                if (array_key_exists($name, $this->filters)) {
                    $queryBuilder = $this->{$this->filters[$name]->getMethod()}($value, $alias, $queryBuilder, $joinManager);
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * Process the sort parameter
     * @param  string       $sortParameter  Value of the sort parameter
     * @param  string       $alias          Alias
     * @param  QueryBuilder $queryBuilder   Query Builder
     * @param  JoinManager  $joinManager    Join Manager
     * @return QueryBuilder                 Altered Query Builder
     */
    public function processSorts($sortParameter, $alias, $queryBuilder, JoinManager $joinManager)
    {
        $sorts = explode(',', $sortParameter);
        foreach($sorts as $sort) {
            // Get direction
            if (substr($sort, 0, 1) === '-') {
                $direction = 'DESC';
                $sort = substr($sort, 1);
            } else {
                $direction = 'ASC';
            }

            // Check if in a relation
            if (false === strpos($sort, '.')) {
                // Check that the attribute is real and sortable
                if (array_key_exists($sort, $this->getAttributes()) && $this->getAttributes()[$sort]->getSortable()) {
                    $queryBuilder->addOrderBy($alias . '.' . $this->getAttributes()[$sort]->getProperty(), $direction);
                }
            } else {
                $attributeExtract = $joinManager->extractAttribute($sort);
                if ($attributeExtract->getAttribute()->getSortable()) {
                    $queryBuilder->addOrderBy($attributeExtract->getAliasChain() . '.' . $attributeExtract->getAttribute()->getProperty(), $direction);
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * Default paginator, uses page[number] and page[size]
     * @param  array        $pageParameters Value of the sort parameter
     * @param  string       $alias          Alias
     * @param  QueryBuilder $queryBuilder   Query Builder
     * @param  FindResult   $result         Find result object to add page info to
     * @return QueryBuilder                 Altered query builder
     */
    public function processPagination($pageParameters, $alias, $queryBuilder, FindResult $result)
    {
        if (isset($pageParameters['number'])){
            $number = $pageParameters['number'];
        } else {
            $number = 1;
        }

        if (isset($pageParameters['size'])) {
            $size = $pageParameters['size'];
            // Enforce a limit though
            if ($size > 1000) {
                $size = 1000;
            }
        } else {
            $size = 25;
        }

        $queryBuilder->setMaxResults($size);
        $queryBuilder->setFirstResult($size * ($number - 1));

        $result->setPageNumber($number);
        $result->setPageSize($size);

        return $queryBuilder;
    }

    /**
     * Hook to make last minute changes to the querybuilder before the query is created
     * @param  QueryBuilder $queryBuilder Query Builder
     * @return QueryBuilder               Query Builder
     */
    public function beforeQuery($queryBuilder)
    {
        return $queryBuilder;
    }

    /**
     * Hook to change the results before they are returned by the find method
     * @param  FindResult $result Results
     * @return array              Results
     */
    public function afterFind(FindResult $result)
    {
        return $result;
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
     * Get relationships
     * @return array Relationships
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Get an Relationship by its json name
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

    /**
     * Get the value of Whether the resource can be deleted
     * @return boolean
     */
    public function getAllowDelete()
    {
        return $this->allowDelete;
    }

    /**
     * Set the value of Whether the resource can be deleted
     * @param boolean allowDelete
     * @return self
     */
    public function setAllowDelete($allowDelete)
    {
        $this->allowDelete = $allowDelete;
        return $this;
    }
}
