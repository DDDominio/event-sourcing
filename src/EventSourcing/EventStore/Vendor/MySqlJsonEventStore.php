<?php

namespace DDDominio\EventSourcing\EventStore\Vendor;

use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Common\EventStreamInterface;
use DDDominio\EventSourcing\EventStore\AbstractEventStore;
use DDDominio\EventSourcing\EventStore\ConcurrencyException;
use DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException;
use DDDominio\EventSourcing\EventStore\InitializableInterface;
use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\Version;

class MySqlJsonEventStore extends AbstractEventStore implements InitializableInterface
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;
    const STREAMS_TABLE = 'streams';
    const EVENTS_TABLE = 'events';

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @param \PDO $connection
     * @param SerializerInterface $serializer
     * @param EventUpgrader $eventUpgrader
     */
    public function __construct(
        \PDO $connection,
        SerializerInterface $serializer,
        $eventUpgrader
    ) {
        $this->connection = $connection;
        parent::__construct($serializer, $eventUpgrader);
    }

    /**
     * @param string $streamId
     * @param int $start
     * @param int $count
     * @return EventStreamInterface
     */
    public function readStreamEvents($streamId, $start = 1, $count = null)
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

        $storedEvents = array_map(function($event) {
            return new StoredEvent(
                $event['id'],
                $event['stream_id'],
                $event['type'],
                $event['event'],
                $event['metadata'],
                new \DateTimeImmutable($event['occurred_on']),
                Version::fromString($event['version'])
            );
        }, $results);

        return $this->domainEventStreamFromStoredEvents($storedEvents);
    }

    /**
     * @param string $streamId
     * @return EventStreamInterface
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

        $storedEvents = array_map(function($event) {
            return new StoredEvent(
                $event['id'],
                $event['stream_id'],
                $event['type'],
                $event['event'],
                $event['metadata'],
                new \DateTimeImmutable($event['occurred_on']),
                Version::fromString($event['version'])
            );
        }, $results);

        return $this->domainEventStreamFromStoredEvents($storedEvents);
    }

    /**
     * @return EventStreamInterface[]
     */
    public function readAllStreams()
    {
        // TODO: Implement readAllStreams() method.
    }

    /**
     * @return EventStreamInterface
     */
    public function readAllEvents()
    {
        $stmt = $this->connection->prepare(
            'SELECT *
             FROM events'
        );
        $stmt->execute();
        $results = $stmt->fetchAll();

        $storedEvents = array_map(function($event) {
            return new StoredEvent(
                $event['id'],
                $event['stream_id'],
                $event['type'],
                $event['event'],
                $event['metadata'],
                new \DateTimeImmutable($event['occurred_on']),
                Version::fromString($event['version'])
            );
        }, $results);

        return $this->domainEventStreamFromStoredEvents($storedEvents);
    }

    /**
     * @param string $streamId
     * @param StoredEvent[] $storedEvents
     * @param int $expectedVersion
     * @throws \Exception
     */
    protected function appendStoredEvents($streamId, $storedEvents, $expectedVersion)
    {
        $this->connection->beginTransaction();
        try {
            if (!$this->streamExists($streamId)) {
                $stmt = $this->connection
                    ->prepare('INSERT INTO streams (id) VALUES (:streamId)');
                $stmt->bindValue(':streamId', $streamId);
                $stmt->execute();
            }
            foreach ($storedEvents as $storedEvent) {
                $stmt = $this->connection->prepare(
                    'INSERT INTO events (stream_id, type, event, metadata, occurred_on, version)
                 VALUES (:streamId, :type, :event, :metadata, :occurredOn, :version)'
                );
                $stmt->bindValue(':streamId', $streamId);
                $stmt->bindValue(':type', $storedEvent->type());
                $stmt->bindValue(':event', $storedEvent->data());
                $stmt->bindValue(':metadata', $storedEvent->metadata());
                $stmt->bindValue(':occurredOn', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
                $stmt->bindValue(':version', $storedEvent->version());
                $stmt->execute();
            }
            $streamFinalVersion = $this->streamVersion($streamId);
            if (count($storedEvents) !== $streamFinalVersion - $expectedVersion) {
                throw ConcurrencyException::fromVersions(
                    $this->streamVersion($streamId),
                    $expectedVersion
                );
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param string $streamId
     * @return bool
     */
    protected function streamExists($streamId)
    {
        $stmt = $this->connection
            ->prepare('SELECT COUNT(*) FROM streams WHERE id = :streamId');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        return boolval($stmt->fetchColumn());
    }
    /**
     * @param string $streamId
     * @return int
     */
    protected function streamVersion($streamId)
    {
        $stmt = $this->connection
            ->prepare('SELECT COUNT(*) FROM events WHERE stream_id = :streamId');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }

    /**
     * @param string $type
     * @param Version $version
     * @return EventStreamInterface
     */
    protected function readStoredEventsOfTypeAndVersion($type, $version)
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
                $result['metadata'],
                new \DateTimeImmutable($result['occurred_on']),
                Version::fromString($result['version'])
            );
        }, $results);

        return new EventStream($storedEvents);
    }

    public function initialize()
    {
        try {
            $this->connection->beginTransaction();

            $this->connection->exec(
                'CREATE TABLE `'.self::STREAMS_TABLE.'` (
                    `id` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`)
                )'
            );

            $this->connection->exec(
                'CREATE TABLE `'.self::EVENTS_TABLE.'` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `stream_id` varchar(255) NOT NULL,
                    `type` varchar(255) NOT NULL,
                    `event` json NOT NULL,
                    `metadata` json NOT NULL,
                    `occurred_on` datetime NOT NULL,
                    `version` varchar(255) NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `stream_id` (`stream_id`),
                    CONSTRAINT `events_ibfk_1` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`)
                )'
            );

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function initialized()
    {
        try {
            $result = $this->connection->query('SELECT 1 FROM `'.self::STREAMS_TABLE.'` LIMIT 1');
        } catch (\Exception $e) {
            return false;
        }
        return $result !== false;
    }

    /**
     * @param string $streamId
     * @param \DateTimeImmutable $datetime
     * @return int
     * @throws EventStreamDoesNotExistException
     */
    public function getStreamVersionAt($streamId, \DateTimeImmutable $datetime)
    {
        if (!$this->streamExists($streamId)) {
            throw EventStreamDoesNotExistException::fromStreamId($streamId);
        }
        $stmt = $this->connection->prepare(
            'SELECT COUNT(*)
             FROM events
             WHERE stream_id = :streamId
             AND occurred_on <= :occurred_on'
        );
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':occurred_on', $datetime->format('Y-m-d H:i:s'));
        $stmt->execute();
        return intval($stmt->fetchColumn());
    }
}
