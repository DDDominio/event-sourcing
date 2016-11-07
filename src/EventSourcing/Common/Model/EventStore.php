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
     * @param string $streamId
     * @param Snapshot $snapshot
     * @return
     */
    public function addSnapshot($streamId, $snapshot);

    /**
     * @param string $streamId
     * @return Snapshot|null
     */
    public function findLastSnapshot($streamId);
}
