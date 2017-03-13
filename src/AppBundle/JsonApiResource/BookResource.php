<?php

namespace AppBundle\JsonApiResource;

use JsonApiBundle\Annotation\Resource;
use JsonApiBundle\Annotation\Attribute;
use JsonApiBundle\Annotation\HasOne;
use JsonApiBundle\Annotation\Filter;
use JsonApiBundle\Annotation\Validator;

use JsonApiBundle\Extra\SearchableResource;

/**
 * @Resource(entity="AppBundle\Entity\Book")
 */
class BookResource extends SearchableResource
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
    public function genreFilter($value, $alias, $queryBuilder, $joins)
    {
        $queryBuilder->andWhere($alias . '.genre = :genre');
        $queryBuilder->setParameter('genre', $value);
        return $queryBuilder;
    }

    /**
     * @{inheritDoc}
     */
    protected function getSearchableEntityFields()
    {
        return [
            'genre',
            'title',
            ['property' => 'author.name', 'joinType' => 'outer']
        ];
    }
}
