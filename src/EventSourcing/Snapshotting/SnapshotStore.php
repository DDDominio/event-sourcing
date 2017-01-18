<?php

namespace DDDominio\EventSourcing\Snapshotting;

interface SnapshotStore
{
    /**
     * @param Snapshot $snapshot
     */
    public function addSnapshot($snapshot);

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return Snapshot|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId);

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return Snapshot|null
     */
    public function findNearestSnapshotToVersion($aggregateClass, $aggregateId, $version);
}
