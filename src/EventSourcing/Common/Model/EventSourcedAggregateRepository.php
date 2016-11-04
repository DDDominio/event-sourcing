<?php

namespace EventSourcing\Common\Model;

class EventSourcedAggregateRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @param EventStore $eventStore
     */
    public function __construct($eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @param EventSourcedAggregate $aggregate
     */
    public function save($aggregate)
    {
        $this->eventStore->appendToStream('streamId', $aggregate->changes(), $aggregate->originalVersion());
        $aggregate->commitChanges();
    }
}
