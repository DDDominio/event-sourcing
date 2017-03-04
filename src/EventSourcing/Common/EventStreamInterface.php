<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\EventInterface;

interface EventStreamInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param EventInterface[] $events
     * @return EventStreamInterface
     */
    public function append($events);

    /**
     * @return EventInterface[]
     */
    public function events();

    /**
     * @param int $offset
     * @return EventInterface
     * @throws \OutOfBoundsException
     */
    public function get($offset);

    /**
     * @return EventInterface
     * @throws \OutOfBoundsException
     */
    public function last();

    /**
     * @return bool
     */
    public function isEmpty();

    /**
     * @param \Closure $closure
     * @return EventStream
     */
    public function filter(\Closure $closure);

    /**
     * @param \Closure $closure
     * @return EventStream
     */
    public function map(\Closure $closure);

    /**
     * @param int $offset
     * @param int|null $length
     * @return EventStream
     */
    public function slice($offset, $length = null);
}
