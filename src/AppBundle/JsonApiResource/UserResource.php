<?php

namespace AppBundle\JsonApiResource;

use JsonApiBundle\Annotation\Resource;
use JsonApiBundle\Annotation\Attribute;
use JsonApiBundle\Annotation\HasMany;
use JsonApiBundle\Annotation\Filter;
use JsonApiBundle\Annotation\Validator;

use JsonApiBundle\JsonApiResource\JsonApiResource;
use JsonApiBundle\JsonApiResource\Resource as ApiResource;

/**
 * @Resource(entity="AppBundle\Entity\User")
 */
class UserResource extends ApiResource
{
    /**
     * @Attribute
     */
    public $username;

    /**
     * @HasMany
     */
    public $foos;

    /**
     * @Filter(name="search")
     */
    public function searchFilter($value, $alias, $queryBuilder, $joins)
    {
        return $queryBuilder;
    }

    /**
     * @Filter(name="username")
     */
    public function usernameFilter($value, $alias, $queryBuilder, $joins)
    {
        $queryBuilder->andWhere($alias . '.username = :username');
        $queryBuilder->setParameter('username', $value);
        return $queryBuilder;
    }

    /**
     * @Validator(errorTitle="Stupid User", errorDetail="User is too stupid to create")
     */
    public function stopStupidUsers($entity)
    {
        return $entity->getUsername() !== 'lighthart';
    }
}
