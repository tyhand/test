<?php

namespace AppBundle\JsonApiResource;

use JsonApiBundle\Annotation\Resource;
use JsonApiBundle\Annotation\Attribute;
use JsonApiBundle\Annotation\HasOne;
use JsonApiBundle\Annotation\Filter;
use JsonApiBundle\Annotation\Validator;

use JsonApiBundle\JsonApiResource\JsonApiResource;
use JsonApiBundle\JsonApiResource\Resource as ApiResource;

/**
 * @Resource(entity="AppBundle\Entity\Book")
 */
class BookResource extends ApiResource
{
    /**
     * @Attribute
     */
    public $title;

    /**
     * @Attribute
     */
    public $genre;

    /**
     * @HasOne
     */
    public $author;

    /**
     * @Filter(name="genre")
     */
    public function searchFilter($value, $alias, $queryBuilder, $joins)
    {
        $queryBuilder->andWhere($alias . '.genre = :genre');
        $queryBuilder->setParameter('genre', $value);
        return $queryBuilder;
    }
}
