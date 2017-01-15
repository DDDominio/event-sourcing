<?php

namespace EventSourcing\Common;

use Common\Event;
use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\UpgradableEventStore;
use EventSourcing\Versioning\Version;
use EventSourcing\Versioning\Versionable;
use JMS\Serializer\Serializer;
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
     * @param Event[] $events
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $events, $expectedVersion = null)
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
    }

    /**
     * @param StoredEvent[] $storedEvents
     * @return EventStream
     */
    protected function domainEventStreamFromStoredEvents($storedEvents)
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
     * @param $events
     * @return array
     */
    private function storedEventsFromEvents($streamId, $events)
    {
        $storedEvents = array_map(function (Event $event) use ($streamId) {
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
     * @param string $streamId
     * @param StoredEvent[] $storedEvents
     * @param int|null $expectedVersion
     */
    protected abstract function appendStoredEvents($streamId, $storedEvents, $expectedVersion = null);

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
     * @return EventStream
     */
    protected abstract function readStoredEventsOfTypeAndVersion($type, $version);
}