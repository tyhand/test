<?php

namespace JsonApiBundle\JsonApiResource;

use JsonApiBundle\Annotation as Annotation;
use JsonApiBundle\Util\Inflect;

class ResourceBuilder
{
    /**
     * Hash of formatters from the manager
     * @var array
     */
    private $formatters;

    /**
     * Resource being built
     * @var Resource
     */
    private $resource;

    /**
     * Constructor
     * @param array $formatters Hash of formatters
     */
    public function __construct(array $formatters)
    {
        $this->formatters = $formatters;
    }

    /**
     * Start a new resource
     * @param  string             $class      Class name of the resource
     * @param  AnnotationResource $annotation Annotation
     * @param  string             $directory  Directory to guess entity name by default
     * @return self
     */
    public function createResource($class, Annotation\Resource $annotation, $directory = '/')
    {
        $this->resource = new $class();

        if ($annotation->getName()) {
            $this->resource->setName($annotation->getName());
        } else {
            preg_match('/(\w+)Resource$/', $class, $matches);
            if (isset($matches[1])) {
                $this->resource->setName(Inflect::pluralize(strtolower($matches[1])));
            } else {
                throw new \Exception('Cannot determine resource name');
            }
        }

        if ($annotation->getEntity()) {
            $this->resource->setEntity($annotation->getEntity());
        } else {
            $namespaceParts = explode('/', $directory);
            preg_match('/(\w+)Resource$/', $class, $matches);
            if (isset($matches[1])) {
                $this->resource->setEntity($namespaceParts[0] . '\\Entity\\' . $matches[1]);
            } else {
                throw new \Exception('Cannot determine entity name for resource');
            }
        }
        return $this;
    }

    /**
     * Add an attribute
     * @param  string              $property   Name of the resource's property
     * @param  AnnotationAttribute $annotation Attribute Annotation
     * @return self
     */
    public function addAttribute($property, Annotation\Attribute $annotation)
    {
        // Build the attribute
        $attribute = new Attribute($property);
        if ($annotation->getProperty()) {
            $attribute->setProperty($annotation->getProperty());
        } else {
            $attribute->setProperty($property);
        }

        if ($annotation->getEntity()) {
            $attribute->setEntity($annotation->getEntity());
        }

        if ($annotation->getJsonName()) {
            $attribute->setJsonName($annotation->getJsonName());
        } else {
            $attribute->setJsonName(strtolower(preg_replace('/(?<!^)([A-Z])/', '-$1', $property)));
        }

        if (array_key_exists($annotation->getFormatter(), $this->formatters)) {
            $attribute->setFormatter($this->formatters[$annotation->getFormatter()]);
        } else {
            throw new \Exception('Unrecognized formatter');
        }

        $attribute->setSortable($annotation->getSortable());
        $attribute->setReadOnly($annotation->getReadOnly());

        $this->resource->{$property} = $attribute;
        $this->resource->addAttribute($attribute);
    }

    /**
     * Add a has one relationship
     * @param string           $property   Name of the resource's property
     * @param AnnotationHasOne $annotation Has one annotation
     */
    public function addHasOne($property, Annotation\HasOne $annotation)
    {

    }

    /**
     * Add a has many relationship
     * @param string           $property   Name of the resource's property
     * @param AnnotationHasOne $annotation Has many annotation
     */
    public function addHasMany($property, Annotation\HasMany $annotation)
    {

    }

    /**
     * Add a filter
     * @param string           $method     Name of the resource's method
     * @param AnnotationFilter $annotation Filter annotation
     */
    public function addFilter($method, Annotation\Filter $annotation)
    {

    }

    /**
     * Add a validator
     * @param string              $method     Name of the resource's method
     * @param AnnotationValidator $annotation Validator annotation
     */
    public function addValidator($method, Annotation\Validator $annotation)
    {

    }

    /**
     * Finish the build
     * @return Resource Resource
     */
    public function build()
    {
        return $this->resource;
    }
}
