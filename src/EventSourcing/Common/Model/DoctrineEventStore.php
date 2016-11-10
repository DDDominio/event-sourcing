<?php

namespace EventSourcing\Common\Model;

use Doctrine\DBAL\Connection;

class DoctrineEventStore implements EventStore
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
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
                ->prepare('INSERT INTO events (stream_id, event) VALUES (:streamId, :event)');
            $stmt->bindValue(':streamId', $streamId);
            $stmt->bindValue(':event', serialize($domainEvent));
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
            ->prepare('SELECT event FROM events WHERE stream_id = :streamId LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':offset', (int) $start - 1, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $count, \PDO::PARAM_INT);
        $stmt->execute();
        $serializedEvents = $stmt->fetchAll();

        $unserializedEvents = array_map(function($event) {
            return unserialize($event['event']);
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
            ->prepare('SELECT event FROM events WHERE stream_id = :streamId');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $serializedEvents = $stmt->fetchAll();

        $unserializedEvents = array_map(function($event) {
            return unserialize($event['event']);
        }, $serializedEvents);

        return new EventStream($unserializedEvents);
    }

    /**
     * @param Snapshot $snapshot
     */
    public function addSnapshot($snapshot)
    {
        $stmt = $this->connection
            ->prepare('INSERT INTO snapshots (aggregate_type, aggregate_id, snapshot) VALUES (:aggregateType, :aggregateId, :snapshot)');
        $stmt->bindValue(':aggregateType', $snapshot->aggregateClass());
        $stmt->bindValue(':aggregateId', $snapshot->aggregateId());
        $stmt->bindValue(':snapshot', serialize($snapshot));
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
            ->prepare('SELECT snapshot FROM snapshots WHERE aggregate_type = :aggregateType AND aggregate_id = :aggregateId ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(':aggregateType', $aggregateClass);
        $stmt->bindValue(':aggregateId', $aggregateId);
        $stmt->execute();
        $snapshot = $stmt->fetch();
        return unserialize($snapshot['snapshot']);
    }
}