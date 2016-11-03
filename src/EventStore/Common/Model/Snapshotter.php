<?php

namespace EventStore\Common\Model;

class Snapshotter
{
    private $snapshotStrategies = [];

    /**
     * @param string $aggregateClass
     * @param SnapshotStrategy $snapshotStrategy
     */
    public function addSnapshotStrategy($aggregateClass, $snapshotStrategy)
    {
        $this->snapshotStrategies[$aggregateClass] = $snapshotStrategy;
    }

    /**
     * @param EventSourcedAggregate $aggregate
     * @return Snapshot
     */
    public function takeSnapshot($aggregate)
    {
        return $this->snapshotStrategies[get_class($aggregate)]->buildSnapshotFromAggregate($aggregate);
    }

    /**
     * @param Snapshot $snapshot
     * @return EventSourcedAggregate
     */
    public function translateSnapshot($snapshot)
    {
        return $this->snapshotStrategies[$snapshot->aggregateClass()]->buildAggregateFromSnapshot($snapshot);
    }
}
