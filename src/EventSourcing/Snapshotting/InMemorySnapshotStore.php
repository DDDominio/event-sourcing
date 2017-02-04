<?php

namespace DDDominio\EventSourcing\Snapshotting;

class InMemorySnapshotStore implements SnapshotStoreInterface
{
    /**
     * @var SnapshotInterface[]
     */
    private $snapshots;

    /**
     * @param SnapshotInterface[] $snapshots
     */
    public function __construct(array $snapshots = [])
    {
        $this->snapshots = $snapshots;
    }

    /**
     * @param SnapshotInterface $snapshot
     */
    public function addSnapshot($snapshot)
    {
        $this->snapshots[$snapshot->aggregateClass()][$snapshot->aggregateId()][] = $snapshot;
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @return SnapshotInterface|null
     */
    public function findLastSnapshot($aggregateClass, $aggregateId)
    {
        if (!isset($this->snapshots[$aggregateClass][$aggregateId])) {
            return null;
        }

        $snapshots = $this->snapshots[$aggregateClass][$aggregateId];

        return end($snapshots);
    }

    /**
     * @param string $aggregateClass
     * @param string $aggregateId
     * @param int $version
     * @return SnapshotInterface|null
     */
    public function findNearestSnapshotToVersion($aggregateClass, $aggregateId, $version)
    {
        if (!isset($this->snapshots[$aggregateClass][$aggregateId])) {
            return null;
        }

        /** @var SnapshotInterface[] $snapshots */
        $snapshots = $this->snapshots[$aggregateClass][$aggregateId];

        $previousSnapshot = null;
        foreach ($snapshots as $snapshot) {
            if ($snapshot->version() < $version) {
                $previousSnapshot = $snapshot;
            } else {
                break;
            }
        }
        return $previousSnapshot;
    }
}
