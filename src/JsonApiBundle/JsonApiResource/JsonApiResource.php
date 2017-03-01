<?php

namespace JsonApiBundle\JsonApiResource;

use Doctrine\ORM\EntityManager;

abstract class JsonApiResource
{
    /**
     * List of attributes
     * @var array
     */
    private $attributes = [];

    /**
     * Same as the attribute list just keyed by the json name
     * @var array
     */
    private $attributesByJsonName = [];

    /**
     * Name of the resource
     * @var string
     */
    private $resourceName;

    /**
     * Whether this resource is a composite resource (in that there are multiple backing entities)
     * @var boolean
     */
    private $isComposite;

    /**
     * Name of the backing entity.  If this is a composite this is an array of entity names
     * @var string|array
     */
    private $entityName;

    /**
     * Entity Manager to load existing entites from an id
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Constructor
     * @param EntityManager $entityManager Entity Manager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Add attribute
     * @param Attribute $attribute Attribute
     */
    public function addAttribute(Attribute $attribute)
    {
        $this->attributes[$attribute->getName()] = $attribute;
        $this->attributesByJsonName[$attribute->getJsonName()] = $attribute;
        return $this;
    }

    /**
     * Get the attributes
     * @return array Attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set resource name
     * @param string $resourceName Resource name
     */
    public function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;
    }

    /**
     * Get the name of the resource
     * @return string Resource name
     */
    public function getResourceName()
    {
        return $this->resourceName;
    }

    /**
     * Set the entity name
     * @param string $entityName Name of the entity (or entities seperated by comma)
     */
    public function setEntityName($entityName)
    {
        if (false === strpos($entityName, ',')) {
            $this->isComposite = false;
            $this->entityName = $entityName;
        } else {
            $this->isComposite = true;
            $this->entityName = explode(',', $entityName);
        }
        return $this;
    }

    /**
     * Get the entity name
     * @return string|array Entity name
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Gets the id for the json output
     * @param  mixed  $entity The base entity or entities
     * @return string         Id for json output
     */
    public function getIdForJson($entity)
    {
        if (is_array($entity)) {
            return $entity[0]->getId();
        } else {
            return $entity->getId();
        }
    }

    /**
     * Gets the backing entity from the id in the json api resource
     * @param  string $id Id
     * @return mixed      Entity if exists
     */
    public function getEntityFromId($id)
    {
        return $this->entityManager->getRepository($this->getEntityName())->findOneById($id);
    }

    /**
     * Convert this resource to a php array for json encoding
     * @param  mixed $entity Entity or Entities to convert
     * @return array
     */
    public function toJson($entity)
    {
        $json = [
            'type' => $this->getResourceName(),
            'id' => $this->getIdForJson($entity),
            'attributes' => []
        ];

        foreach($this->getAttributes() as $attribute) {
            $json['attributes'][$attribute->getJsonName()] = $attribute->getValue($entity);
        }

        return $json;
    }

    /**
     * Convert a json input to the entity for this resource
     * @param  array  $data Json input
     * @return mixed        Entity or entities
     */
    public function toEntity($data)
    {
        if (array_key_exists('data', $data)) {
            // Check that the type matches
            if ($data['data']['type'] !== $this->getResourceName()) {
                throw new \Exception('Invalid type for this resource');
            }

            if (array_key_exists('id', $data['data'])) {
                $entity = $this->getEntityFromId($data['data']['id']);
            } else {
                $entity = new $this->entityName();
            }

            foreach($data['data']['attributes'] as $attributeName => $attributeData) {
                if (array_key_exists($attributeName, $this->attributesByJsonName)) {
                    $this->attributesByJsonName[$attributeName]->setValue($entity, $attributeData);
                }
            }

            return $entity;
        } else {
            throw new \Exception('Invalid json');
        }
    }
}
