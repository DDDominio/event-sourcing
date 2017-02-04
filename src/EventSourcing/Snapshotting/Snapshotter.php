<?php

namespace DDDominio\EventSourcing\Snapshotting;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRoot;

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
     * @return SnapshotInterface
     */
    public function takeSnapshot($aggregate)
    {
        return $this->snapshotStrategies[get_class($aggregate)]->buildSnapshotFromAggregate($aggregate);
    }

    /**
     * @param SnapshotInterface $snapshot
     * @return EventSourcedAggregateRoot
     */
    public function translateSnapshot($snapshot)
    {
        return $this->snapshotStrategies[$snapshot->aggregateClass()]->buildAggregateFromSnapshot($snapshot);
    }
}
