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
     * @return bool
     */
    public function isEmpty();
}
