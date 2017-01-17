<?php

namespace EventSourcing\Common;

use Doctrine\DBAL\Connection;
use EventSourcing\Serialization\Serializer;
use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\Version;

class DoctrineEventStore extends AbstractEventStore
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     * @param Serializer $serializer
     * @param EventUpgrader $eventUpgrader
     */
    public function __construct($connection, $serializer, $eventUpgrader)
    {
        parent::__construct($serializer, $eventUpgrader);
        $this->connection = $connection;
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
                new \DateTimeImmutable($result['occurred_on']),
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
                new \DateTimeImmutable($result['occurred_on']),
                Version::fromString($result['version'])
            );
        }, $results);

        return $this->domainEventStreamFromStoredEvents($storedEvents);
    }

    /**
     * @param string $type
     * @param Version $version
     * @return EventStream
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
                new \DateTimeImmutable($result['occurred_on']),
                Version::fromString($result['version'])
            );
        }, $results);

        return new EventStream($storedEvents);
    }

    /**
     * @param string $streamId
     * @param StoredEvent[] $storedEvents
     * @param int $expectedVersion
     */
    protected function appendStoredEvents($streamId, $storedEvents, $expectedVersion)
    {
        $this->connection->transactional(function() use ($streamId, $storedEvents, $expectedVersion) {
            if (!$this->streamExists($streamId)) {
                $stmt = $this->connection
                    ->prepare('INSERT INTO streams (id) VALUES (:streamId)');
                $stmt->bindValue(':streamId', $streamId);
                $stmt->execute();
            }
            foreach ($storedEvents as $storedEvent) {
                $stmt = $this->connection->prepare(
                    'INSERT INTO events (stream_id, type, event, occurred_on, version)
                 VALUES (:streamId, :type, :event, :occurredOn, :version)'
                );
                $stmt->bindValue(':streamId', $streamId);
                $stmt->bindValue(':type', $storedEvent->name());
                $stmt->bindValue(':event', $storedEvent->body());
                $stmt->bindValue(':occurredOn', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
                $stmt->bindValue(':version', $storedEvent->version());
                $stmt->execute();
            }
            $streamFinalVersion = $this->streamVersion($streamId);
            if (count($storedEvents) !== $streamFinalVersion - $expectedVersion) {
                throw new ConcurrencyException();
            }
        });
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
}
