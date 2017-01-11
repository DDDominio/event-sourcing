<?php

namespace EventSourcing\Common;

class StoredEventStream extends EventStream
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param string $id
     * @param StoredEvent[] $storedEvents
     */
    public function __construct($id, $storedEvents)
    {
        $this->id = $id;
        parent::__construct($storedEvents);
    }

    /**
     * @return string
     */
    public function id()
    {
        return $this->id;
    }
}
