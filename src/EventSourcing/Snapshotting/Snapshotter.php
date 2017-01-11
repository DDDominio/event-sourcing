<?php

namespace EventSourcing\Snapshotting;

use EventSourcing\Common\EventSourcedAggregateRoot;

class Snapshotter
{
    private $snapshotStrategies = [];

    /**
     * @param string $aggregateClass
     * @param ReflectionSnapshotTranslator $snapshotStrategy
     */
    public function addSnapshotTranslator($aggregateClass, $snapshotStrategy)
    {
        $this->snapshotStrategies[$aggregateClass] = $snapshotStrategy;
    }

    /**
     * @param EventSourcedAggregateRoot $aggregate
     * @return Snapshot
     */
    public function takeSnapshot($aggregate)
    {
        return $this->snapshotStrategies[get_class($aggregate)]->buildSnapshotFromAggregate($aggregate);
    }

    /**
     * @param Snapshot $snapshot
     * @return EventSourcedAggregateRoot
     */
    public function translateSnapshot($snapshot)
    {
        return $this->snapshotStrategies[$snapshot->aggregateClass()]->buildAggregateFromSnapshot($snapshot);
    }
}
