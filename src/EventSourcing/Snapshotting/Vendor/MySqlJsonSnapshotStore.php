<?php

namespace DDDominio\EventSourcing\Snapshotting\Vendor;

use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotStoreInterface;

class MySqlJsonSnapshotStore implements SnapshotStoreInterface
{
    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param \PDO $connection
     * @param SerializerInterface $serializer
     */
    public function __construct(
        \PDO $connection,
        SerializerInterface $serializer
    ) {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }

    /**
     * @param SnapshotInterface $snapshot
     */
    public function addSnapshot($snapshot)
    {
        $stmt = $this->connection
            ->prepare('INSERT INTO snapshots (aggregate_type, aggregate_id, type, version, snapshot) VALUES (:aggregateType, :aggregateId, :type, :version, :snapshot)');
        $stmt->bindValue(':aggregateType', $snapshot->aggregateClass());
        $stmt->bindValue(':aggregateId', $snapshot->aggregateId());
        $stmt->bindValue(':type', get_class($snapshot));
        $stmt->bindValue(':version', $snapshot->version());
        $stmt->bindValue(':snapshot', $this->serializer->serialize($snapshot));
        $stmt->execute();
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return SnapshotInterface|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId)
    {
        $stmt = $this->connection
            ->prepare('SELECT type, snapshot FROM snapshots WHERE aggregate_type = :aggregateType AND aggregate_id = :aggregateId ORDER BY id DESC LIMIT 1');
        $stmt->bindValue(':aggregateType', $aggregateClass);
        $stmt->bindValue(':aggregateId', $aggregateId);
        $stmt->execute();
        $snapshot = $stmt->fetch();
        return $snapshot ? $this->serializer->deserialize($snapshot['snapshot'], $snapshot['type']) : null;
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return SnapshotInterface|null
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
        return $snapshot ? $this->serializer->deserialize($snapshot['snapshot'], $snapshot['type']) : null;
    }
}
