<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\EventSourcing\Snapshotting\SnapshotStore;

abstract class EventSourcedAggregateRepository
{
    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var SnapshotStore
     */
    private $snapshotStore;

    /**
     * @var AggregateReconstructor
     */
    private $aggregateReconstructor;

    /**
     * @param EventStore $eventStore
     * @param $snapshotStore
     * @param AggregateReconstructor $aggregateReconstructor
     */
    public function __construct(
        EventStore $eventStore,
        SnapshotStore $snapshotStore,
        $aggregateReconstructor
    ) {
        $this->eventStore = $eventStore;
        $this->snapshotStore = $snapshotStore;
        $this->aggregateReconstructor = $aggregateReconstructor;
    }

    /**
     * @param EventSourcedAggregateRoot $aggregate
     */
    public function add($aggregate)
    {
        $this->eventStore->appendToStream(
            $this->streamIdFromAggregate($aggregate),
            $aggregate->changes()
        );
        $aggregate->clearChanges();
    }

    /**
     * @param EventSourcedAggregateRoot $aggregate
     */
    public function save($aggregate)
    {
        $this->eventStore->appendToStream(
            $this->streamIdFromAggregate($aggregate),
            $aggregate->changes(),
            $aggregate->originalVersion()
        );
        $aggregate->clearChanges();
    }

    /**
     * @param string $id
     * @return EventSourcedAggregateRoot
     */
    public function findById($id)
    {
        $snapshot = $this->snapshotStore
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
     * @return EventSourcedAggregateRoot
     */
    public function findByIdAndVersion($id, $version)
    {
        $snapshot = $this->snapshotStore
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
     * @param EventSourcedAggregateRoot $aggregate
     * @return string
     */
    private function streamIdFromAggregate($aggregate)
    {
        return $this->streamIdFromAggregateId($this->aggregateId($aggregate));
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
     * @param EventSourcedAggregateRoot $aggregate
     * @return string
     */
    protected abstract function aggregateId($aggregate);
}
