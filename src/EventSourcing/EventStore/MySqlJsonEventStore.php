<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Common\EventStreamInterface;
use DDDominio\EventSourcing\Serialization\Serializer;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\Version;

class MySqlJsonEventStore extends AbstractEventStore implements EventStore
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @param \PDO $connection
     * @param Serializer $serializer
     * @param EventUpgrader $eventUpgrader
     */
    public function __construct(
        \PDO $connection,
        Serializer $serializer,
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

        $storedEvents = array_map(function($event) {
            return new StoredEvent(
                $event['id'],
                $event['stream_id'],
                $event['type'],
                $event['event'],
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
                    'INSERT INTO events (stream_id, type, event, occurred_on, version)
                 VALUES (:streamId, :type, :event, :occurredOn, :version)'
                );
                $stmt->bindValue(':streamId', $streamId);
                $stmt->bindValue(':type', $storedEvent->type());
                $stmt->bindValue(':event', $storedEvent->body());
                $stmt->bindValue(':occurredOn', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
                $stmt->bindValue(':version', $storedEvent->version());
                $stmt->execute();
            }
            $streamFinalVersion = $this->streamVersion($streamId);
            if (count($storedEvents) !== $streamFinalVersion - $expectedVersion) {
                throw new ConcurrencyException();
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
                new \DateTimeImmutable($result['occurred_on']),
                Version::fromString($result['version'])
            );
        }, $results);

        return new EventStream($storedEvents);
    }
}