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
     * @param Snapshot $snapshot
     */
    public function addSnapshot($snapshot)
    {
        $this->snapshots[$snapshot->aggregateClass()][$snapshot->aggregateId()][] = $snapshot;
    }

    /**
     * @param string $streamId
     * @param int $start
     * @param int $count
     * @return EventStream
     */
    public function readStreamEventsForward($streamId, $start = 1, $count = null)
    {
        $events = $this->streams[$streamId];

        if (isset($count)) {
            $filteredEvents = array_splice($events, $start - 1, $count);
        } else {
            $filteredEvents = array_splice($events, $start - 1);
        }

        $stream = new EventStream($filteredEvents);

        return isset($this->streams[$streamId]) ?
            $stream : EventStream::buildEmpty();
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return Snapshot|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId)
    {
        if (!isset($this->snapshots[$aggregateClass][$aggregateId])) {
            return null;
        }

        $snapshots = $this->snapshots[$aggregateClass][$aggregateId];

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

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return Snapshot|null
     */
    public function findNearestSnapshotToVersion($aggregateClass, $aggregateId, $version)
    {
        if (!isset($this->snapshots[$aggregateClass][$aggregateId])) {
            return null;
        }

        /** @var Snapshot[] $snapshots */
        $snapshots = $this->snapshots[$aggregateClass][$aggregateId];

        $previousSnapshot = null;
        foreach ($snapshots as $snapshot) {
            if ($snapshot->version() < $version) {
                $previousSnapshot = $snapshot;
            } else {
                break;
            }
        }
        return $previousSnapshot;
    }
}
