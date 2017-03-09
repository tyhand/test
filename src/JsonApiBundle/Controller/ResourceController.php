<?php

namespace JsonApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JsonApiBundle\Util\Inflect;

class ResourceController extends Controller
{
    public function resourceIndexAction(Request $request)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $result = $resource->find($request->query);
        $json = ['data' => []];
        foreach($result->getResults() as $entity) {
            $json['data'][] = $resource->toJson($entity);
        }

        return new JsonResponse($json);
    }


    public function resourceCreateAction(Request $request)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->toEntity(json_decode($request->getContent(), true)['data']);

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->persist($entity);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($resource->toJson($entity));
    }


    public function resourceShowAction(Request $request, $id)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $this->getDoctrine()->getManager()->getRepository($resource->getEntity())->findOneById($id);

        return new JsonResponse($resource->toJson($entity));
    }


    public function resourceEditAction(Request $request, $id)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->toEntity(json_decode($request->getContent(), true)['data']);

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($resource->toJson($entity));
    }

    public function resourceShowRelationshipsAction(Request $request, $id, $relationship)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->loadEntityById($id);
        if (!$entity) {
            throw new \Exception('Entity not found');
        }
        $relation = $resource->getRelationshipByJsonName($relationship);
        if (!$relation) {
            throw new \Exception('Relationship not found');
        }

        $data = $relation->getResourceIdentifierJson($entity);

        return new JsonResponse(['data' => $data]);
    }


    public function resourceEditRelationshipsAction(Request $request, $id, $relationship)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->loadEntityById($id);
        if (!$entity) {
            throw new \Exception('Entity not found');
        }
        $relation = $resource->getRelationshipByJsonName($relationship);
        if (!$relation) {
            throw new \Exception('Relationship not found');
        }
        $entity = $relation->addToEntity($entity, json_decode($request->getContent(), true), $this->get('jsonapi.resource_manager'));

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['data' => $relation->getResourceIdentifierJson($entity)]);
    }


    public function resourceAddRelationshipsAction(Request $request, $id, $relationship)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->loadEntityById($id);
        if (!$entity) {
            throw new \Exception('Entity not found');
        }
        $relation = $resource->getRelationshipByJsonName($relationship);
        if (!$relation) {
            throw new \Exception('Relationship not found');
        } elseif (!($relation instanceof \JsonApiBundle\JsonApiResource\HasManyRelationship)) {
            throw new \Exception('Method is only for Has Many Relationships');
        }

        $relation->setModeToAdd();
        $entity = $relation->addToEntity($entity, json_decode($request->getContent(), true), $this->get('jsonapi.resource_manager'));

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['data' => $relation->getResourceIdentifierJson($entity)]);
    }


    public function resourceRemoveRelationshipsAction(Request $request, $id, $relationship)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->loadEntityById($id);
        if (!$entity) {
            throw new \Exception('Entity not found');
        }
        $relation = $resource->getRelationshipByJsonName($relationship);
        if (!$relation) {
            throw new \Exception('Relationship not found');
        } elseif (!($relation instanceof \JsonApiBundle\JsonApiResource\HasManyRelationship)) {
            throw new \Exception('Method is only for Has Many Relationships');
        }

        $relation->setModeToRemove();
        $entity = $relation->addToEntity($entity, json_decode($request->getContent(), true), $this->get('jsonapi.resource_manager'));

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['data' => $relation->getResourceIdentifierJson($entity)]);
    }


    /**
     * Gets the name of the resource, which is taken from the controller name by default
     * @return string Resource name
     */
    public function getResourceName()
    {
        preg_match('/(\w+)Controller$/', static::class, $matches);
        if (isset($matches[1])) {
            return Inflect::pluralize(strtolower($matches[1]));
        } else {
            return null;
        }
    }

    /**
     * Generate the error response from an array or errors
     * @param  array        $errors Error array
     * @return JsonResponse         Response
     */
    private function createErrorResponse($errors)
    {
        $json = [];
        foreach($errors as $error) {
            $json[] = $error->toJson();
        }
        return new JsonResponse(['errors' => $json], 422);
    }
}
