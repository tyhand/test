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


    public function resourceShowRelationshipsAction(Request $request, $id, $relationship)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->loadEntityById($id);
        if (!$entity) {
            throw new \Exception('Entity not found');
        }

    }

    public function resourceCreateAction(Request $request)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());

        $entity = $resource->toEntity(json_decode($request->getContent(), true)['data']);

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

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($resource->toJson($entity));
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
}
