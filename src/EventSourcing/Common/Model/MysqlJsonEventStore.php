<?php

namespace EventSourcing\Common\Model;

use JMS\Serializer\Serializer;

class MysqlJsonEventStore implements EventStore
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param \PDO $connection
     */
    public function __construct(\PDO $connection, Serializer $serializer)
    {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * @param string $streamId
     * @param DomainEvent[] $domainEvents
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $domainEvents, $expectedVersion = 0)
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

        foreach ($domainEvents as $domainEvent) {
            $stmt = $this->connection
                ->prepare('INSERT INTO events (stream_id, type, event) VALUES (:streamId, :type, :event)');
            $stmt->bindValue(':streamId', $streamId);
            $stmt->bindValue(':type', get_class($domainEvent));
            $stmt->bindValue(':event', $this->serializer->serialize($domainEvent, 'json'));
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
        $stmt = $this->connection
            ->prepare('SELECT type, event FROM events WHERE stream_id = :streamId LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':offset', (int) $start - 1, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $count, \PDO::PARAM_INT);
        $stmt->execute();
        $serializedEvents = $stmt->fetchAll();

        $unserializedEvents = array_map(function($event) {
            return $this->serializer->deserialize($event['event'], $event['type'], 'json');
        }, $serializedEvents);

        return new EventStream($unserializedEvents);
    }

    /**
     * @param string $streamId
     * @return EventStream
     */
    public function readFullStream($streamId)
    {
        $stmt = $this->connection
            ->prepare('SELECT type, event FROM events WHERE stream_id = :streamId');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $serializedEvents = $stmt->fetchAll();

        $unserializedEvents = array_map(function($event) {
            return $this->serializer->deserialize($event['event'], $event['type'], 'json');
        }, $serializedEvents);

        return new EventStream($unserializedEvents);
    }

    /**
     * @param Snapshot $snapshot
     */
    public function addSnapshot($snapshot)
    {
        $stmt = $this->connection
            ->prepare('INSERT INTO snapshots (aggregate_type, aggregate_id, type, version, snapshot) VALUES (:aggregateType, :aggregateId, :type, :version, :snapshot)');
        $stmt->bindValue(':aggregateType', $snapshot->aggregateClass());
        $stmt->bindValue(':aggregateId', $snapshot->aggregateId());
        $stmt->bindValue(':type', get_class($snapshot));
        $stmt->bindValue(':version', $snapshot->version());
        $stmt->bindValue(':snapshot', $this->serializer->serialize($snapshot, 'json'));
        $stmt->execute();
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return Snapshot|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId)
    {
        $stmt = $this->connection
            ->prepare('SELECT type, snapshot FROM snapshots WHERE aggregate_type = :aggregateType AND aggregate_id = :aggregateId ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(':aggregateType', $aggregateClass);
        $stmt->bindValue(':aggregateId', $aggregateId);
        $stmt->execute();
        $snapshot = $stmt->fetch();
        return $snapshot ? $this->serializer->deserialize($snapshot['snapshot'], $snapshot['type'], 'json') : null;
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return Snapshot|null
     */
    public function findNearestSnapshotToVersion($aggregateClass, $aggregateId, $version)
    {
        $stmt = $this->connection
            ->prepare('SELECT type, snapshot FROM snapshots WHERE aggregate_type = :aggregateType AND aggregate_id = :aggregateId AND version <= :version ORDER BY version DESC LIMIT 1');
        $stmt->bindValue(':aggregateType', $aggregateClass);
        $stmt->bindValue(':aggregateId', $aggregateId);
        $stmt->bindValue(':version', $version);
        $stmt->execute();
        $snapshot = $stmt->fetch();
        return $snapshot ? $this->serializer->deserialize($snapshot['snapshot'], $snapshot['type'], 'json') : null;
    }
}