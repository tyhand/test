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
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var string
     */
    private $rootDirectory;

    /**
     * Constructor
     * @param string        $namespace        Namespace to read from
     * @param string        $directory        Directory to read from
     * @param string        $rootDirectory    Root Directory
     * @param Reader        $annotationReader Doctrine Annotation Reader
     */
    public function __construct($namespace, $directory, $rootDirectory, Reader $annotationReader)
    {
        $this->namespace = $namespace;
        $this->directory = $directory;
        $this->rootDirectory = $rootDirectory;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Read in the resources
     * @param  array $formatters Hash of formatters
     * @return array             Array of resources
     */
    public function readResources(array $formatters)
    {
        $resources = [];
        $path = $this->rootDirectory . '/../src/' . $this->directory;
        $finder = new Finder();
        $finder->files()->in($path);
        foreach($finder as $file) {
            $class = $this->namespace . '\\' . $file->getBaseName('.php');
            $reflection = new \ReflectionClass($class);
            $annotation = $this->annotationReader->getClassAnnotation(
                $reflection,
                'JsonApiBundle\Annotation\Resource'
            );
            if ($annotation) {
                $resourceBuilder = new ResourceBuilder($formatters);
                $resourceBuilder->createResource($class, $annotation, $this->directory);

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

                $resource = $resourceBuilder->build();
                $resources[$resource->getName()] = $resource;
            }


        }

        return $resources;
    }
}
