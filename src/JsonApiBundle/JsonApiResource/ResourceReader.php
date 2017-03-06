<?php

namespace JsonApiBundle\JsonApiResource;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Config\FileLocator;

use Doctrine\ORM\EntityManager;

use JsonApiBundle\Util\Inflect;

class ResourceReader
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * List of resource objects
     * @var array
     */
    private $resources;

    /**
     * Formatters
     * @var array
     */
    private $formatters;

    /**
     * Constructor
     * @param Reader        $annotationReader Doctrine Annotation Reader
     */
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        $this->resources = [];
        $this->formatters = [];
    }

    /**
     * Add an unread resource to the reader
     * @param  Resource $resource Resource to add
     * @return self
     */
    public function addResource(Resource $resource)
    {
        $this->resources[$resource->getName()] = $resource;
        return $this;
    }

    /**
     * Add a formatter
     * @param Formatter $formatter Formatter to add
     */
    public function addFormatter(Formatter $formatter)
    {
        $this->formatters[$formatter->getName()] = $formatter;
        return $this;
    }

    /**
     * Check if the reader has a resource by a given name
     * @param  string  $name Name to check for
     * @return boolean       Whether the reader has that resource
     */
    public function hasResource($name)
    {
        return array_key_exists($name, $this->resources);
    }

    /**
     * Read a resource
     * @param  string    $name Name of the resource to read
     * @return Reasource       Built resource
     */
    public function readResource($name)
    {
        if (!$this->hasResource($name)) {
            return null;
        }

        $class = get_class($this->resources[$name]);
        $reflection = new \ReflectionClass($class);
        $annotation = $this->annotationReader->getClassAnnotation(
            $reflection,
            'JsonApiBundle\Annotation\Resource'
        );
        if ($annotation) {
            $resourceBuilder = new ResourceBuilder($this->resources[$name], $this->formatters);
            $resourceBuilder->startResource($annotation);

            foreach($reflection->getProperties() as $property) {
                $attributeAnnotation = $this->annotationReader->getPropertyAnnotation(
                    $property,
                    'JsonApiBundle\Annotation\Attribute'
                );
                if ($attributeAnnotation) {
                    $resourceBuilder->addAttribute($property->name, $attributeAnnotation);
                    continue;
                }

                $hasOneAnnotation = $this->annotationReader->getPropertyAnnotation(
                    $property,
                    'JsonApiBundle\Annotation\HasOne'
                );
                if ($hasOneAnnotation) {
                    $resourceBuilder->addHasOne($property->name, $hasOneAnnotation);
                    continue;
                }

                $hasManyAnnotation = $this->annotationReader->getPropertyAnnotation(
                    $property,
                    'JsonApiBundle\Annotation\HasMany'
                );
                if ($hasManyAnnotation) {
                    $resourceBuilder->addHasMany($property->name, $hasManyAnnotation);
                    continue;
                }
            }

            foreach($reflection->getMethods() as $method) {
                $filterAnnotation = $this->annotationReader->getMethodAnnotation(
                    $method,
                    'JsonApiBundle\Annotation\Filter'
                );
                if ($filterAnnotation) {
                    $resourceBuilder->addFilter($method->name, $filterAnnotation);
                    continue;
                }

                $validatorAnnotation = $this->annotationReader->getMethodAnnotation(
                    $method,
                    'JsonApiBundle\Annotation\Validator'
                );
                if ($validatorAnnotation) {
                    $resourceBuilder->addValidator($method->name, $validatorAnnotation);
                    continue;
                }
            }

            return $resourceBuilder->build();
        } else {
            return null;
        }
    }
}
