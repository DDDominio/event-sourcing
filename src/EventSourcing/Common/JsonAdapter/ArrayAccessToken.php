<?php

namespace EventSourcing\Common\JsonAdapter;

class ArrayAccessToken
{
    /**
     * @var int
     */
    private $index;

    /**
     * @param int $index
     */
    public function __construct($index)
    {
        $this->index = $index;
    }

    /**
     * @return int
     */
    public function index()
    {
        return $this->index;
    }
}
