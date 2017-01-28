<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Common\EventStreamInterface;
use DDDominio\EventSourcing\Serialization\Serializer;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\UpgradableEventStore;
use DDDominio\EventSourcing\Versioning\Version;
use Ramsey\Uuid\Uuid;

abstract class AbstractEventStore implements EventStore, UpgradableEventStore
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    /**
     * @var callable[]
     */
    private $eventListeners;

    /**
     * @param Serializer $serializer
     * @param EventUpgrader $eventUpgrader
     */
    public function __construct(
        $serializer,
        $eventUpgrader
    ) {
        $this->serializer = $serializer;
        $this->eventUpgrader = $eventUpgrader;
    }

    /**
     * @param string $streamId
     * @param DomainEvent[] $events
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $events, $expectedVersion = self::EXPECTED_VERSION_EMPTY_STREAM)
    {
        if ($this->streamExists($streamId)) {
            $this->assertOptimisticConcurrency($streamId, $expectedVersion);
        } else {
            $this->assertEventStreamExistence($expectedVersion);
        }
        $this->appendStoredEvents(
            $streamId,
            $this->storedEventsFromEvents($streamId, $events),
            $expectedVersion
        );
        if (isset($this->eventListeners[EventStore::AFTER_EVENTS_APPENDED])) {
            foreach ($this->eventListeners[EventStore::AFTER_EVENTS_APPENDED] as $eventListener) {
                $eventListener($events);
            }
        }
    }

    /**
     * @param StoredEvent[] $storedEvents
     * @return EventStreamInterface
     */
    protected function domainEventStreamFromStoredEvents($storedEvents)
    {
        $domainEvents = array_map(function (StoredEvent $storedEvent) {
            $this->eventUpgrader->migrate($storedEvent);
            return $this->serializer->deserialize(
                $storedEvent->body(),
                $storedEvent->type()
            );
        }, $storedEvents);
        return new EventStream($domainEvents);
    }

    /**
     * @param string $streamId
     * @param int $expectedVersion
     * @throws ConcurrencyException
     */
    private function assertOptimisticConcurrency($streamId, $expectedVersion)
    {
        if ($this->streamVersion($streamId) !== $expectedVersion) {
            throw new ConcurrencyException();
        }
    }

    /**
     * @param int $expectedVersion
     * @throws EventStreamDoesNotExistException
     */
    private function assertEventStreamExistence($expectedVersion)
    {
        if ($expectedVersion > 0) {
            throw new EventStreamDoesNotExistException();
        }
    }

    /**
     * @param string $streamId
     * @param DomainEvent[] $events
     * @return array
     */
    private function storedEventsFromEvents($streamId, $events)
    {
        $storedEvents = array_map(function (DomainEvent $event) use ($streamId) {
            return new StoredEvent(
                $this->nextStoredEventId(),
                $streamId,
                get_class($event->data()),
                $this->serializer->serialize($event->data()),
                $this->serializer->serialize($event->metadata()),
                $event->occurredOn(),
                is_null($event->version()) ? Version::fromString('1.0') : $event->version()
            );
        }, $events);
        return $storedEvents;
    }

    /**
     * @return string
     */
    protected function nextStoredEventId()
    {
        return Uuid::uuid4()->toString();
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
     * @param $eventStoreEvent
     * @param callable $callable
     */
    public function addEventListener($eventStoreEvent, callable $callable)
    {
        $this->eventListeners[$eventStoreEvent][] = $callable;
    }

    /**
     * @param string $streamId
     * @param StoredEvent[] $storedEvents
     * @param int $expectedVersion
     */
    protected abstract function appendStoredEvents($streamId, $storedEvents, $expectedVersion);

    /**
     * @param string $streamId
     * @return bool
     */
    protected abstract function streamExists($streamId);

    /**
     * @param string $streamId
     * @return int
     */
    protected abstract function streamVersion($streamId);

    /**
     * @param string $type
     * @param Version $version
     * @return EventStreamInterface
     */
    protected abstract function readStoredEventsOfTypeAndVersion($type, $version);
}