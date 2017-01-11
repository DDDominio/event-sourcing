<?php

namespace EventSourcing\Snapshotting;

use EventSourcing\Common\EventSourcedAggregateRoot;

interface SnapshotTranslator
{
    /**
     * @param EventSourcedAggregateRoot $aggregate
     * @return Snapshot
     */
    public function buildSnapshotFromAggregate($aggregate);

    /**
     * @param Snapshot $snapshot
     * @return EventSourcedAggregateRoot
     */
    public function buildAggregateFromSnapshot($snapshot);
}
