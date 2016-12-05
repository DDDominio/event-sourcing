<?php

namespace EventSourcing\Common\Model;

interface EventStore
{
    /**
     * @param string $streamId
     * @param Event[] $events
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $events, $expectedVersion = 0);

    /**
     * @param string $streamId
     * @param int $start
     * @param int $count
     * @return EventStream
     */
    public function readStreamEventsForward($streamId, $start = 1, $count = null);

    /**
     * @param string $streamId
     * @return EventStream
     */
    public function readFullStream($streamId);

    /**
     * @param Snapshot $snapshot
     */
    public function addSnapshot($snapshot);

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return Snapshot|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId);

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return Snapshot|null
     */
    public function findNearestSnapshotToVersion($aggregateClass, $aggregateId, $version);
}
