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
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param string $id
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($id, \DateTimeImmutable $occurredOn)
    {
        $this->id = $id;
        $this->occurredOn = $occurredOn;
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
