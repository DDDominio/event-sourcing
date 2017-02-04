<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\EventInterface;

class EventStream implements EventStreamInterface
{
    /**
     * @var EventInterface[]
     */
    private $events;

    /**
     * @param EventInterface[] $events
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
     * @param EventInterface[] $events
     * @return EventStream
     */
    public function append($events)
    {
        return new self(array_merge($this->events(), $events));
    }

    /**
     * @return EventInterface[]
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
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->events());
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->events);
    }
}
