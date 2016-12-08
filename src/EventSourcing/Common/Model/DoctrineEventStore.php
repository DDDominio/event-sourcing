<?php

namespace EventSourcing\Common\Model;

use Doctrine\DBAL\Connection;
use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\UpgradableEventStore;
use EventSourcing\Versioning\Version;
use EventSourcing\Versioning\Versionable;
use JMS\Serializer\Serializer;

class DoctrineEventStore implements EventStore, UpgradableEventStore
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    /**
     * @param Connection $connection
     * @param Serializer $serializer
     * @param EventUpgrader $eventUpgrader
     */
    public function __construct($connection, $serializer, $eventUpgrader)
    {
        $this->connection = $connection;
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
    public function appendToStream($streamId, $events, $expectedVersion = 0)
    {
        $stmt = $this->connection
            ->prepare('SELECT COUNT(*) FROM streams WHERE id = :streamId');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $streamExists = boolval($stmt->fetchColumn());

        if (!$streamExists) {
            if ($expectedVersion !== 0) {
                throw new EventStreamDoesNotExistException();
            }

            $stmt = $this->connection
                ->prepare('INSERT INTO streams (id) VALUES (:streamId)');
            $stmt->bindValue(':streamId', $streamId);
            $stmt->execute();
        }

        $stmt = $this->connection
            ->prepare('SELECT COUNT(*) FROM events WHERE stream_id = :streamId');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $actualVersion = intval($stmt->fetchColumn());

        if ($expectedVersion !== $actualVersion) {
            throw new ConcurrencyException();
        }

        foreach ($events as $event) {
            if ($event instanceof Versionable) {
                $version = $event->version();
            } else {
                $version = Version::fromString('1.0');
            }
            $stmt = $this->connection->prepare(
                'INSERT INTO events (stream_id, type, event, occurredOn, version)
                 VALUES (:streamId, :type, :event, :occurredOn, :version)'
            );
            $stmt->bindValue(':streamId', $streamId);
            $stmt->bindValue(':type', get_class($event));
            $stmt->bindValue(':event', $this->serializer->serialize($event, 'json'));
            $stmt->bindValue(':occurredOn', $event->occurredOn()->format('Y-m-d H:i:s'));
            $stmt->bindValue(':version', $version);
            $stmt->execute();
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
        if (!isset($count)) {
            $count = self::MAX_UNSIGNED_BIG_INT;
        }
        $stmt = $this->connection->prepare(
            'SELECT *
             FROM events
             WHERE stream_id = :streamId
             LIMIT :limit
             OFFSET :offset'
        );
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':offset', (int) $start - 1, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $count, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $storedEvents = array_map(function($result) {
            return new StoredEvent(
                $result['id'],
                $result['stream_id'],
                $result['type'],
                $result['event'],
                new \DateTimeImmutable($result['occurredOn']),
                Version::fromString($result['version'])
            );
        }, $results);

        return $this->domainEventStreamFromStoredEvents($storedEvents);
    }

    /**
     * @param string $streamId
     * @return EventStream
     */
    public function readFullStream($streamId)
    {
        $stmt = $this->connection->prepare(
            'SELECT *
             FROM events
             WHERE stream_id = :streamId'
        );
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $storedEvents = array_map(function($result) {
            return new StoredEvent(
                $result['id'],
                $result['stream_id'],
                $result['type'],
                $result['event'],
                new \DateTimeImmutable($result['occurredOn']),
                Version::fromString($result['version'])
            );
        }, $results);

        return $this->domainEventStreamFromStoredEvents($storedEvents);
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

        /** @var StoredEvent $storedEvent */
        foreach ($stream as $storedEvent) {
            $this->eventUpgrader->migrate($storedEvent, $to);
            $this->saveStoredEvent($storedEvent);
        }
    }

    /**
     * @param string $type
     * @param Version $version
     * @return EventStream
     */
    private function readStoredEventsOfTypeAndVersion($type, $version)
    {
        $stmt = $this->connection->prepare(
            'SELECT *
             FROM events
             WHERE type = :type
             AND version = :version'
        );
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':version', $version);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $storedEvents = array_map(function($result) {
            return new StoredEvent(
                $result['id'],
                $result['stream_id'],
                $result['type'],
                $result['event'],
                new \DateTimeImmutable($result['occurredOn']),
                Version::fromString($result['version'])
            );
        }, $results);

        return new EventStream($storedEvents);
    }

    /**
     * @param StoredEvent $storedEvent
     */
    private function saveStoredEvent(StoredEvent $storedEvent)
    {
        $stmt = $this->connection->prepare(
            'UPDATE events
             SET type = :type, event = :event, version = :version
             WHERE id = :id'
        );
        $stmt->bindValue(':type', $storedEvent->name());
        $stmt->bindValue(':event', $storedEvent->body());
        $stmt->bindValue(':version', $storedEvent->version());
        $stmt->bindValue(':id', $storedEvent->id());
        $stmt->execute();
    }
}
