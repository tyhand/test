<?php

namespace JsonApiBundle\JsonApiResource;

/**
 * Simple util to help manage joins added to a query builder
 */
class JoinManager
{
    /**
     * Joins
     * @var array
     */
    private $joins;

    /**
     * Alias of the root
     * @var string
     */
    private $alias;

    /**
     * Constructor
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
        $this->joins = [$alias];
    }

    /**
     * Adds a requested join to the query builder
     * @param string       $joinString   Join string (e.g. 'entity.relation.attribute')
     * @param QueryBuilder $queryBuilder Query builder to add join too
     */
    public function addJoin($joinString, $queryBuilder)
    {
        $parts = explode('.', $joinString);
        // Check that the first piece is in the chain
        if (!in_array($parts[0], $this->joins)) {
            throw new \Exception('Unrooted join');
        }

        for($i = 1; $i < count($parts) - 1; $i++) {
            if (!in_array($parts[$i], $this->joins)) {
                // Add the part to the join, and join it in the query builder
                $queryBuilder->join($parts[$i - 1] . '.' . $parts[$i], $parts[$i]);
                $this->joins[] = $parts[$i];
            }
        }

        return $queryBuilder;
    }
}
