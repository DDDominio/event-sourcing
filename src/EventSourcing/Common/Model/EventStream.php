<?php

namespace EventSourcing\Common\Model;

use Traversable;

class EventStream implements \IteratorAggregate
{
    /**
     * @var DomainEvent[]
     */
    private $events;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(array $events)
    {
        $this->events = $events;
    }

    /**
     * @return DomainEvent[]
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->events());
    }
}
