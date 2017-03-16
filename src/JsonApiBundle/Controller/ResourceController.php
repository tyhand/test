<?php

namespace JsonApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JsonApiBundle\Util\Inflect;
use JsonApiBundle\JsonApiResource\IncludeManager;
use JsonApiBundle\JsonApiResource\LinkGenerator;

class ResourceController extends Controller
{
    public function resourceIndexAction(Request $request)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $result = $resource->find($request->query);
        $includeManager = $this->createIncludesManager($request);
        $linkGenerator = $this->createLinkGenerator($request);
        $json = ['data' => []];
        foreach($result->getResults() as $entity) {
            $json['data'][] = $resource->toJson($entity, $includeManager);
        }

        if ($includeManager->hasData()) {
            $json['included'] = $includeManager->toJson();
        }

        $json['links'] = $linkGenerator->generatePaginationLinks($result);
        $json['meta'] = $result->generateMetaJson();

        return new JsonResponse($this->postProcessJson($request, $json));
    }


    public function resourceCreateAction(Request $request)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->toEntity(json_decode($request->getContent(), true)['data']);

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterCreateAttribute(), $entity);
        }

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->persist($entity);
        $this->getDoctrine()->getManager()->flush();

        $json = $resource->toJson($entity);

        return new JsonResponse($this->postProcessJson($request, $json));
    }

    public function resourceShowAction(Request $request, $id)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $this->getDoctrine()->getManager()->getRepository($resource->getEntity())->findOneById($id);

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterViewAttribute(), $entity);
        }

        $includeManager = $this->createIncludesManager($request);
        $json = ['data' => $resource->toJson($entity, $includeManager)];

        if ($includeManager->hasData()) {
            $json['included'] = $includeManager->toJson();
        }

        return new JsonResponse($this->postProcessJson($request, $json));
    }

    public function resourceEditAction(Request $request, $id)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->toEntity(json_decode($request->getContent(), true)['data']);

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterEditAttribute(), $entity);
        }

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse($this->postProcessJson($request, $resource->toJson($entity)));
    }


    public function resourceDeleteAction(Request $request, $id)
    {
        $resource = $this->get('jsonapi.resource_manager')->getResource($this->getResourceName());
        $entity = $resource->loadEntityById($id);

        if (!$resource->getAllowDelete()) {
            throw $this->createAccessDeniedException('This operation is not allowed');
        }

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterDeleteAttribute(), $entity);
        }

        $this->getDoctrine()->getManager()->remove($entity);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([]);
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

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterViewAttribute(), $entity);
        }

        $data = $relation->getResourceIdentifierJson($entity);

        $json = ['data' => $data];

        return new JsonResponse($this->postProcessJson($request, $json));
    }

    public function resourceShowRelationshipsFullAction(Request $request, $id, $relationship)
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

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterViewAttribute(), $entity);
        }

        $object = $relation->getRelatedFromEntity($entity);
        $relatedResource = $this->get('jsonapi.resource_manager')->getResource($relation->getResource());
        $includeManager = $this->createIncludesManager($request);

        if (is_array($object) || $object instanceof \Doctrine\Common\Collections\Collection) {
            $json = ['data' => []];
            foreach($object as $part) {
                $json['data'][] = $relatedResource->toJson($part, $includeManager);
            }
        } else {
            $json = ['data' => $relatedResource->toJson($object, $includeManager)];
        }

        if ($includeManager->hasData()) {
            $json['included'] = $includeManager->toJson();
        }

        return new JsonResponse($this->postProcessJson($request, $json));
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

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterEditAttribute(), $entity);
        }

        $entity = $relation->addToEntity($entity, json_decode($request->getContent(), true), $this->get('jsonapi.resource_manager'));

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        $json = ['data' => $relation->getResourceIdentifierJson($entity)];

        return new JsonResponse($this->postProcessJson($request, $json));
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

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterEditAttribute(), $entity);
        }

        $relation->setModeToAdd();
        $entity = $relation->addToEntity($entity, json_decode($request->getContent(), true), $this->get('jsonapi.resource_manager'));

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        $json = ['data' => $relation->getResourceIdentifierJson($entity)];

        return new JsonResponse($this->postProcessJson($request, $json));
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

        if ($resource->getUseVoters()) {
            $this->denyAccessUnlessGranted($resource->getVoterEditAttribute(), $entity);
        }

        $relation->setModeToRemove();
        $entity = $relation->addToEntity($entity, json_decode($request->getContent(), true), $this->get('jsonapi.resource_manager'));

        $errors = $resource->validate($entity, $this->get('validator'));
        if (0 < count($errors)) {
            return $this->createErrorResponse($errors);
        }

        $this->getDoctrine()->getManager()->flush();

        $json = ['data' => $relation->getResourceIdentifierJson($entity)];

        return new JsonResponse($this->postProcessJson($request, $json));
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
    protected function createErrorResponse($errors)
    {
        $json = [];
        foreach($errors as $error) {
            $json[] = $error->toJson();
        }
        return new JsonResponse(['errors' => $json], 422);
    }

    /**
     * Creates the includes manager
     * @param  Request        $request Http Request
     * @return IncludeManager          Include Manager
     */
    protected function createIncludesManager(Request $request)
    {
        if ($request->query->has('include')) {
            return new IncludeManager($this->get('jsonapi.resource_manager'), explode(',', $request->query->get('include')));
        } else {
            return new IncludeManager($this->get('jsonapi.resource_manager'));
        }
    }

    /**
     * Create the link generator
     * @param  Request       $request Request
     * @return LinkGenerator          Link Generator
     */
    protected function createLinkGenerator(Request $request)
    {
        return new LinkGenerator($request);
    }

    /**
     * Add final touches to the json output
     * @param  Request $request Request
     * @param  array   $json    Json hash
     * @return array            Altered Json hash
     */
    protected function postProcessJson(Request $request, $json)
    {
        $json['jsonapi'] = ['version' => '1.0'];

        return $json;
    }
}
