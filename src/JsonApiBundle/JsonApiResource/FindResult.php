<?php

namespace JsonApiBundle\JsonApiResource;

/**
 * Small class to hold the result of a find operation and its meta data
 */
class FindResult
{
    /**
     * Number of results before pagination
     * @var int
     */
    private $count;

    /**
     * Results
     * @var array
     */
    private $results;

    /**
     * Page Number
     * @var int
     */
    private $pageNumber;

    /**
     * Page Size
     * @var int
     */
    private $pageSize;

    /**
     * Get the value of Number of results before pagination
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set the value of Number of results before pagination
     * @param int count
     * @return self
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Get the value of Results
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Set the value of Results
     * @param array results
     * @return self
     */
    public function setResults(array $results)
    {
        $this->results = $results;
        return $this;
    }

    /**
     * Get the value of Page Number
     * @return int
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Set the value of Page Number
     * @param int pageNumber
     * @return self
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;
        return $this;
    }

    /**
     * Get the value of Page Size
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * Set the value of Page Size
     * @param int pageSize
     * @return self
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
        return $this;
    }
}
