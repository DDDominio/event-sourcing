<?php

namespace EventSourcing\Common\Model;

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
     * @return EventSourcedAggregate
     */
    public function findById($id)
    {
        $snapshot = $this->eventStore
            ->findLastSnapshot($this->aggregateClass(), $id);

        $streamId = $this->streamIdFromAggregateId($id);
        if ($snapshot) {
            $stream = $this->eventStore
                ->readStreamEventsForward($streamId, $snapshot->version() + 1);
        } else {
            $stream = $this->eventStore->readFullStream($streamId);
        }

        return $this->aggregateReconstructor->reconstitute(
            $this->aggregateClass(),
            $stream,
            $snapshot
        );
    }

    /**
     * @param string $id
     * @param int $version
     * @return EventSourcedAggregate
     */
    public function findByIdAndVersion($id, $version)
    {
        $snapshot = $this->eventStore
            ->findNearestSnapshotToVersion($this->aggregateClass(), $id, $version);

        $streamId = $this->streamIdFromAggregateId($id);
        if ($snapshot) {
            $stream = $this->eventStore
                ->readStreamEventsForward(
                    $streamId,
                    $snapshot->version() + 1,
                    $version - $snapshot->version()
                );
        } else {
            $stream = $this->eventStore
                ->readStreamEventsForward(
                    $streamId,
                    1,
                    $version
                );
        }
        return $this->aggregateReconstructor->reconstitute(
            $this->aggregateClass(),
            $stream,
            $snapshot
        );
    }

    /**
     * @param EventSourcedAggregate $aggregate
     * @return string
     */
    protected function streamIdFromAggregate($aggregate)
    {
        return $this->aggregateClass() . '-' . $this->aggregateId($aggregate);
    }

    /**
     * @param string $aggregateId
     * @return string
     */
    protected function streamIdFromAggregateId($aggregateId)
    {
        return $this->aggregateClass() . '-' . $aggregateId;
    }

    /**
     * @return string
     */
    protected abstract function aggregateClass();

    /**
     * @param EventSourcedAggregate $aggregate
     * @return string
     */
    protected abstract function aggregateId($aggregate);
}
