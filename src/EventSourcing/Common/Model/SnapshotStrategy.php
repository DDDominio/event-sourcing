<?php

namespace EventSourcing\Common\Model;

interface SnapshotStrategy
{
    /**
     * @param EventSourcedAggregate $aggregate
     * @return Snapshot
     */
    public function buildSnapshotFromAggregate($aggregate);

    /**
     * @param Snapshot $snapshot
     * @return EventSourcedAggregate
     */
    public function buildAggregateFromSnapshot($snapshot);
}
