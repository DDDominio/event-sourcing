<?php

namespace DDDominio\EventSourcing\Snapshotting;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRoot;

interface SnapshotTranslatorInterface
{
    /**
     * @param EventSourcedAggregateRoot $aggregate
     * @return SnapshotInterface
     */
    public function buildSnapshotFromAggregate($aggregate);

    /**
     * @param SnapshotInterface $snapshot
     * @return EventSourcedAggregateRoot
     */
    public function buildAggregateFromSnapshot($snapshot);
}
