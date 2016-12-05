<?php

namespace EventSourcing\Common\Model;

use Traversable;

class EventStream implements \IteratorAggregate
{
    /**
     * @var Event[]
     */
    private $events;

    /**
     * @param Event[] $events
     */
    public function __construct(array $events)
    {
        $this->events = $events;
    }

    /**
     * @return EventStream
     */
    public static function buildEmpty()
    {
        return new self([]);
    }

    /**
     * @param Event[] $events
     * @return EventStream
     */
    public function append($events)
    {
        return new self(array_merge($this->events(), $events));
    }

    /**
     * @return Event[]
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->events) === 0;
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
