<?php

namespace EventSourcing\Common\Model;

use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\UpgradableEventStore;
use EventSourcing\Versioning\Version;
use EventSourcing\Versioning\Versionable;
use JMS\Serializer\Serializer;
use Ramsey\Uuid\Uuid;

class InMemoryEventStore implements EventStore, UpgradableEventStore
{
    /**
     * @var StoredEventStream[]
     */
    private $streams;

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
     */
    public function __construct(
        $serializer,
        $eventUpgrader,
        array $streams = []
    ) {
        $this->serializer = $serializer;
        $this->streams = $streams;
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

    /**
     * @param string $type
     * @param Version $from
     * @param Version $to
     */
    public function migrate($type, $from, $to)
    {
        $stream = $this->readStoredEventsOfTypeAndVersion($type, $from);

        foreach ($stream as $event) {
            $this->eventUpgrader->migrate($event, $to);
        }
    }

    /**
     * @param string $type
     * @param Version $version
     * @return EventStream
     */
    private function readStoredEventsOfTypeAndVersion($type, $version)
    {
        $storedEvents = [];
        foreach ($this->streams as $stream) {
            /** @var StoredEvent $event */
            foreach ($stream as $event) {
                if ($event->name() === $type && $event->version()->equalTo($version)) {
                    $storedEvents[] = $event;
                }
            }
        }
        return new EventStream($storedEvents);
    }
}
