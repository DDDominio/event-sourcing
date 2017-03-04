<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\Common\EventInterface;
use DDDominio\EventSourcing\Common\EventStream;

class IdentifiedEventStream extends EventStream
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param string $id
     * @param EventInterface[] $events
     */
    public function __construct($id, $events)
    {
        $this->id = $id;
        parent::__construct($events);
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }
}
