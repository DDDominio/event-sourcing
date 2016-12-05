<?php

namespace EventSourcing\Common\Model;

use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\Version;
use EventSourcing\Versioning\Versionable;
use JMS\Serializer\Serializer;
use Ramsey\Uuid\Uuid;

class InMemoryEventStore implements EventStore
{
    /**
     * @var StoredEventStream[]
     */
    private $streams;

    /**
     * @var Snapshot[]
     */
    private $snapshots;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    /**
     * @param Serializer $serializer
     * @param EventUpgrader $eventUpgrader
     * @param StoredEventStream[] $streams
     * @param array $snapshots
     */
    public function __construct(
        $serializer,
        $eventUpgrader,
        array $streams = [],
        array $snapshots = []
    ) {
        $this->serializer = $serializer;
        $this->streams = $streams;
        $this->snapshots = $snapshots;
        $this->eventUpgrader = $eventUpgrader;
    }

    /**
     * @param string $streamId
     * @param Event[] $events
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $events, $expectedVersion = null)
    {
        $storedEvents = array_map(function(Event $event) use ($streamId) {
            if ($event instanceof Versionable) {
                $version = $event->version();
            } else {
                $version = Version::fromString('1.0');
            }

            return new StoredEvent(
                $this->nextStoredEventId(),
                $streamId,
                get_class($event),
                $this->serializer->serialize($event, 'json'),
                $event->occurredOn(),
                $version
            );
        }, $events);


        if (isset($this->streams[$streamId])) {
            $this->assertOptimisticConcurrency($streamId, $expectedVersion);
            $this->streams[$streamId] = $this->streams[$streamId]->append($storedEvents);
        } else {
            $this->assertEventStreamExistence($expectedVersion);
            $this->streams[$streamId] = new StoredEventStream($streamId, $storedEvents);
        }
    }

    /**
     * @param string $streamId
     * @return EventStream
     */
    public function readFullStream($streamId)
    {
        if (isset($this->streams[$streamId])) {
            return $this->domainEventStreamFromStoredEvents(
                $this->streams[$streamId]->events()
            );
        } else {
            return EventStream::buildEmpty();
        }
    }

    /**
     * @param string $streamId
     * @param int $start
     * @param int $count
     * @return EventStream
     */
    public function readStreamEventsForward($streamId, $start = 1, $count = null)
    {
        if (!isset($this->streams[$streamId])) {
            return EventStream::buildEmpty();
        }

        $storedEvents = $this->streams[$streamId]->events();

        if (isset($count)) {
            $filteredStoredEvents = array_splice($storedEvents, $start - 1, $count);
        } else {
            $filteredStoredEvents = array_splice($storedEvents, $start - 1);
        }
        return $this->domainEventStreamFromStoredEvents($filteredStoredEvents);
    }

    /**
     * @param Snapshot $snapshot
     */
    public function addSnapshot($snapshot)
    {
        $this->snapshots[$snapshot->aggregateClass()][$snapshot->aggregateId()][] = $snapshot;
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
     * @return string
     */
    private function nextStoredEventId()
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * @param StoredEvent[] $storedEvents
     * @return EventStream
     */
    private function domainEventStreamFromStoredEvents($storedEvents)
    {
        $domainEvents = array_map(function (StoredEvent $storedEvent) {
            $this->eventUpgrader->migrate($storedEvent);
            return $this->serializer->deserialize(
                $storedEvent->body(),
                $storedEvent->name(),
                'json'
            );
        }, $storedEvents);
        return new EventStream($domainEvents);
    }
}
