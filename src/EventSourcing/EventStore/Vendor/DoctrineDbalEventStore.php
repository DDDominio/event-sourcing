<?php

namespace DDDominio\EventSourcing\EventStore\Vendor;

use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Common\EventStreamInterface;
use DDDominio\EventSourcing\EventStore\AbstractEventStore;
use DDDominio\EventSourcing\EventStore\ConcurrencyException;
use DDDominio\EventSourcing\EventStore\StoredEvent;
use Doctrine\DBAL\Connection;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\Version;
use Doctrine\DBAL\Schema\Schema;
use DDDominio\EventSourcing\EventStore\InitializableInterface;

class DoctrineDbalEventStore extends AbstractEventStore implements InitializableInterface
{
    const MAX_UNSIGNED_BIG_INT = 9223372036854775807;
    const STREAMS_TABLE = 'streams';
    const EVENTS_TABLE = 'events';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     * @param SerializerInterface $serializer
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
        // TODO: Implement readAllEvents() method.
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

    /**
     * Initialize the Event Store
     */
    public function initialize()
    {
        $schema = new Schema();

        $streamTable = $schema->createTable(self::STREAMS_TABLE);
        $streamTable->addColumn('id', 'string');
        $streamTable->setPrimaryKey(array("id"));

        $eventsTable = $schema->createTable(self::EVENTS_TABLE);
        $eventsTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $eventsTable->addColumn('stream_id', 'string');
        $eventsTable->addColumn('type', 'string');
        $eventsTable->addColumn('event', 'text');
        $eventsTable->addColumn('metadata', 'text');
        $eventsTable->addColumn('occurred_on', 'datetime');
        $eventsTable->addColumn('version', 'string');
        $eventsTable->setPrimaryKey(['id']);
        $eventsTable->addForeignKeyConstraint($streamTable, ['stream_id'], ['id']);

        $queries = $schema->toSql($this->connection->getDatabasePlatform());
        $this->connection->transactional(function(Connection $connection) use ($queries) {
            foreach ($queries as $query) {
                $connection->exec($query);
            }
        });
    }

    /**
     * Check if the Event Store has been initialized
     *
     * @return bool
     */
    public function initialized()
    {
        return $this->connection->getSchemaManager()->tablesExist([self::STREAMS_TABLE]);
    }
}
