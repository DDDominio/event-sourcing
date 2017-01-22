<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\Common\Event;

interface EventStreamInterface extends \IteratorAggregate
{
    /**
     * @param Event[] $events
     * @return EventStreamInterface
     */
    public function append($events);

    /**
     * @return Event[]
     */
    public function events();

    /**
     * @return bool
     */
    public function isEmpty();
}
