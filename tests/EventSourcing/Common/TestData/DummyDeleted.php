<?php

namespace Tests\EventSourcing\Common\TestData;

use EventSourcing\Common\AggregateDeleterDomainEvent;

class DummyDeleted implements AggregateDeleterDomainEvent
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
