<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\AggregateDeleter;
use EventSourcing\Common\Model\DomainEvent;

class DummyDeleted implements DomainEvent, AggregateDeleter
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }
}
