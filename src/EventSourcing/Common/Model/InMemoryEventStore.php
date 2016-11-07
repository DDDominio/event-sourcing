<?php

namespace EventSourcing\Common\Model;

class InMemoryEventStore implements EventStore
{
    /**
     * @var array
     */
    private $streams;

    /**
     * @var Snapshot[]
     */
    private $snapshots;

    /**
     * @param array $streams
     * @param array $snapshots
     */
    public function __construct(array $streams = [], array $snapshots = [])
    {
        $this->streams = $streams;
        $this->snapshots = $snapshots;
    }

    /**
     * @param string $streamId
     * @param DomainEvent[] $domainEvents
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $domainEvents, $expectedVersion = null)
    {
        if (isset($this->streams[$streamId])) {
            $this->assertOptimisticConcurrency($streamId, $expectedVersion);
            $this->streams[$streamId] = $this->streams[$streamId]->append($domainEvents);
        } else {
            $this->assertEventStreamExistence($expectedVersion);
            $this->streams[$streamId] = new EventStream($domainEvents);
        }
    }

    /**
     * @param string $streamId
     * @return EventStream
     */
    public function readFullStream($streamId)
    {
        return isset($this->streams[$streamId]) ?
            $this->streams[$streamId] : EventStream::buildEmpty();
    }

    /**
     * @param string $streamId
     * @param Snapshot $snapshot
     */
    public function addSnapshot($streamId, $snapshot)
    {
        $this->snapshots['streamId'][] = $snapshot;
    }

    /**
     * @param string $streamId
     * @return Snapshot|null
     */
    public function findLastSnapshot($streamId)
    {
        if (!isset($this->snapshots[$streamId])) {
            return null;
        }

        $snapshots = $this->snapshots[$streamId];

        return end($snapshots);
    }

    /**
     * @param $expectedVersion
     * @throws EventStreamDoesNotExistException
     */
    private function assertEventStreamExistence($expectedVersion)
    {
        if (isset($expectedVersion)) {
            throw new EventStreamDoesNotExistException();
        }
    }

    /**
     * @param $streamId
     * @param $expectedVersion
     * @throws ConcurrencyException
     */
    private function assertOptimisticConcurrency($streamId, $expectedVersion)
    {
        if (count($this->streams[$streamId]->events()) !== $expectedVersion) {
            throw new ConcurrencyException();
        }
    }
}
