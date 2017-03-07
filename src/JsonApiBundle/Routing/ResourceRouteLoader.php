<?php

namespace JsonApiBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use JsonApiBundle\JsonApiResource\ResourceManager;

class ResourceRouteLoader extends Loader
{
    /**
     * Resource Manager
     * @var ResourceManager
     */
    private $manager;

    /**
     * Constructor
     * @param ResourceManager $manager Resource Manager
     */
    public function __construct(ResourceManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @{inheritDoc}
     */
    public function load($resource, $type= null)
    {
        if (!class_exists($resource, true)) {
            throw new \RuntimeException($resource . ' does not exist');
        }

        $controllerReflection = new \ReflectionClass($resource);
        if (!$controllerReflection->hasMethod('getResourceName')) {
            throw new \RuntimeException($resource . ' does not have the required method "getResourceName"');
        }

        $controller = $controllerReflection->newInstance();
        $resourceName = $controller->getResourceName();

        if (!$resourceName) {
            throw new \RuntimeException('Could not determine resource name for "'. $resource .'"');
        }

        // Load the api resource
        $apiResource = $this->manager->getResource($resourceName);
        if (!$apiResource) {
            throw new \RuntimeException('Could not load the resource "' . $resource . '"');
        }

        $routes = new RouteCollection();

        // Index
        $routes->add($resourceName . '_index', new Route('/' . $resourceName, ['_controller' => $resource . '::resourceIndexAction'], [], [], '', [], ['GET']));

        // Show
        $routes->add($resourceName . '_show', new Route('/' . $resourceName . '/{id}', ['_controller' => $resource . '::resourceShowAction'], ['id' => '\d+'], [], '', [], ['GET']));

        // New
        $routes->add($resourceName . '_create', new Route('/' . $resourceName, ['_controller' => $resource . '::resourceCreateAction'], [], [], '', [], ['POST']));

        // Edit
        $routes->add($resourceName . '_edit', new Route('/' . $resourceName . '/{id}', ['_controller' => $resource . '::resourceEditAction'], ['id' => '\d+'], [], '', [], ['PATCH']));

        // Delete
        $routes->add($resourceName . '_delete', new Route('/' . $resourceName . '/{id}', ['_controller' => $resource . '::resourceDeleteAction'], ['id' => '\d+'], [], '', [], ['DELETE']));

        // Relationships
        $routes->add(
            $resourceName . '_relationships',
            new Route(
                '/' . $resourceName . '/{id}/relationships/{relationship}',
                ['_controller' => $resource . '::resourceShowRelationshipAction'],
                [
                    'id' => '\d+',
                    'relationship' => '\w+'
                ],
                [],
                '',
                [],
                ['GET']
            )
        );

        $routes->add(
            $resourceName . '_set_relationships',
            new Route(
                '/' . $resourceName . '/{id}/relationships/{relationship}',
                ['_controller' => $resource . '::resourceSetRelationshipAction'],
                [
                    'id' => '\d+',
                    'relationship' => '\w+'
                ],
                [],
                '',
                [],
                ['PATCH']
            )
        );

        $routes->add(
            $resourceName . '_add_relationships',
            new Route(
                '/' . $resourceName . '/{id}/relationships/{relationship}',
                ['_controller' => $resource . '::resourceAddRelationshipAction'],
                [
                    'id' => '\d+',
                    'relationship' => '\w+'
                ],
                [],
                '',
                [],
                ['POST']
            )
        );

        $routes->add(
            $resourceName . '_remove_relationships',
            new Route(
                '/' . $resourceName . '/{id}/relationships/{relationship}',
                ['_controller' => $resource . '::resourceRemoveRelationshipAction'],
                [
                    'id' => '\d+',
                    'relationship' => '\w+'
                ],
                [],
                '',
                [],
                ['DELETE']
            )
        );

        return $routes;
    }

    /**
     * @{inheritDoc}
     */
    public function supports($resource, $type = null)
    {
        return 'jsonapi_resource' === $type;
    }
}
