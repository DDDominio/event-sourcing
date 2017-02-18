<?php

namespace DDDominio\EventSourcing\Snapshotting;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRootInterface;

interface SnapshotTranslatorInterface
{
    /**
     * @param EventSourcedAggregateRootInterface $aggregate
     * @return SnapshotInterface
     */
    public function buildSnapshotFromAggregate($aggregate);

    /**
     * @param SnapshotInterface $snapshot
     * @return EventSourcedAggregateRootInterface
     */
    public function buildAggregateFromSnapshot($snapshot);
}
