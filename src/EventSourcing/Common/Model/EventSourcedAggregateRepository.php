<?php

namespace EventSourcing\Common\Model;

use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;

abstract class EventSourcedAggregateRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var AggregateReconstructor
     */
    private $aggregateReconstructor;

    /**
     * @param EventStore $eventStore
     * @param AggregateReconstructor $aggregateReconstructor
     */
    public function __construct($eventStore, $aggregateReconstructor)
    {
        $this->eventStore = $eventStore;
        $this->aggregateReconstructor = $aggregateReconstructor;
    }

    /**
     * @param EventSourcedAggregate $aggregate
     */
    public function add($aggregate)
    {
        $this->eventStore->appendToStream(
            $this->streamIdFromAggregate($aggregate),
            $aggregate->changes()
        );
        $aggregate->commitChanges();
    }

    /**
     * @param EventSourcedAggregate $aggregate
     */
    public function save($aggregate)
    {
        $this->eventStore->appendToStream(
            $this->streamIdFromAggregate($aggregate),
            $aggregate->changes(),
            $aggregate->originalVersion()
        );
        $aggregate->commitChanges();
    }

    /**
     * @param string $id
     * @return DummyEventSourcedAggregate
     */
    public function findById($id)
    {
        return $this->aggregateReconstructor->reconstitute(
            DummyEventSourcedAggregate::class,
            $this->eventStore->readFullStream($id)
        );
    }

    /**
     * @param $aggregate
     * @return string
     */
    protected function streamIdFromAggregate($aggregate)
    {
        return $this->aggregateType() . '-' . $this->aggregateId($aggregate);
    }

    /**
     * @return string
     */
    protected abstract function aggregateType();

    /**
     * @param EventSourcedAggregate $aggregate
     * @return string
     */
    protected abstract function aggregateId($aggregate);
}
