<?php

namespace EventSourcing\Common\Model;

interface EventStore
{
    /**
     * @param string $streamId
     * @param DomainEvent[] $domainEvents
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $domainEvents, $expectedVersion = 0);

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
}
