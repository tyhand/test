<?php

namespace JsonApiBundle\JsonApiResource;

use Doctrine\ORM\EntityManager;

class ResourceManager
{
    /**
     * @var ResourceReader
     */
    private $resourceReader;

    /**
     * @var EntityLoader
     */
    private $entityLoader;

    /**
     * Array of resources keyed by name
     * @var array
     */
    private $resources;

    /**
     * Array of formatters keyed by name
     * @var array
     */
    private $formatters;

    /**
     * Constructor
     * @param ResourceReader $resourceReader Resource finder
     */
    public function __construct(ResourceReader $resourceReader, EntityLoader $entityLoader)
    {
        $this->resourceReader = $resourceReader;
        $this->entityLoader = $entityLoader;
        $this->formatters = [];
    }

    /**
     * Load an entity from resource
     * @param  ResourceIdentifier $identifier Identifier
     * @return mixed                          Loaded entity
     */
    public function loadEntityFromIdentifier(ResourceIdentifier $identifier)
    {
        $resource = $this->getResource($identifier->getType());
        if (!$resource) {
            throw new \Exception('Type not found');
        }
        return $this->entityLoader->loadEntity($resource->getEntity(), $identifier->getId());
    }

    /**
     * Add a formatter
     * @param  Formatter $formatter Formatter
     * @return self
     */
    public function addFormatter(Formatter $formatter)
    {
        $this->formatters[$formatter->getName()] = $formatter;
        return $this;
    }

    /**
     * Get the entity loader
     * @return EntityLoader Entity Loader
     */
    public function getEntityLoader()
    {
        return $this->entityLoader;
    }

    /**
     * Get a resource by name
     * @param  string $name Name of the resource to get
     * @return JsonApiResource Resource if exists
     */
    public function getResource($name)
    {
        if (array_key_exists($name, $this->getResources())) {
            return $this->getResources()[$name];
        } else {
            return null;
        }
    }

    /**
     * Get the resources from the resource reader
     * @return array Array of resources
     */
    private function getResources()
    {
        if (null === $this->resources) {
            $this->resources = $this->resourceReader->readResources($this->formatters);
            foreach($this->resources as $resource) {
                $resource->setManager($this);
            }
        }

        return $this->resources;
    }
}
