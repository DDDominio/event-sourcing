<?php

namespace DDDominio\EventSourcing\EventStore\Projection;

use DDDominio\EventSourcing\Common\DomainEvent;

class Projector
{
    /**
     * @var array
     */
    private $emittedEventsByStream = [];

    /**
     * @param string $stream
     * @param mixed $event
     */
    public function emit($stream, $event)
    {
        $this->emittedEventsByStream[$stream][] = DomainEvent::produceNow($event);
    }

    /**
     * @return array
     */
    public function emittedEventsByStream()
    {
        return $this->emittedEventsByStream;
    }
}
