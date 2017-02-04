<?php

namespace DDDominio\EventSourcing\Snapshotting;

interface SnapshotStoreInterface
{
    /**
     * @param SnapshotInterface $snapshot
     */
    public function addSnapshot($snapshot);

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return SnapshotInterface|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId);

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return SnapshotInterface|null
     */
    public function findNearestSnapshotToVersion($aggregateClass, $aggregateId, $version);
}
