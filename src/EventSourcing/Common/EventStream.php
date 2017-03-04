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
     * @param EventInterface|EventInterface[] $events
     * @return EventStream
     */
    public function append($events)
    {
        if (!is_array($events)) {
            $events = [$events];
        }
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
     * @param int $offset
     * @return EventInterface
     * @throws \OutOfBoundsException
     */
    public function get($offset)
    {
        if (!isset($this->events[$offset])) {
            throw new \OutOfBoundsException();
        }
        return $this->events[$offset];
    }

    /**
     * @return EventInterface
     * @throws \OutOfBoundsException
     */
    public function last()
    {
        if ($this->isEmpty()) {
            throw new \OutOfBoundsException();
        }
        return end($this->events);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->events);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->events);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->events());
    }

    /**
     * @param \Closure $closure
     * @return EventStream
     */
    public function filter(\Closure $closure)
    {
        return new self(array_values(array_filter($this->events, $closure)));
    }

    /**
     * @param \Closure $closure
     * @return EventStream
     */
    public function map(\Closure $closure)
    {
        return new self(array_map($closure, $this->events));
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return EventStream
     */
    public function slice($offset, $length = null)
    {
        return new self(array_slice($this->events, $offset, $length));
    }
}
