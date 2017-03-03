<?php

namespace JsonApiBundle\JsonApiResource;

class HasOneRelationship extends Relationship
{
    /**
     * @{inheritDoc}
     */
    public function addToJson($entity, array $json)
    {

        return $json;
    }

    /**
     * @{inheritDoc}
     */
    public function addToEntity($entity, array $relationData, ResourceManager $manager)
    {

        return $entity;
    }
}
