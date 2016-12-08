<?php

namespace EventSourcing\Snapshotting;

use EventSourcing\Common\Model\EventSourcedAggregate;

interface SnapshotTranslator
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
