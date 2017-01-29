<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\AggregateReconstructor;
use DDDominio\EventSourcing\Common\EventSourcedAggregateRepository;
use DDDominio\EventSourcing\EventStore\EventStore;
use DDDominio\EventSourcing\Snapshotting\SnapshotStore;

class DummyEventSourcedAggregateRepository extends EventSourcedAggregateRepository
{
    public function __construct(
        EventStore $eventStore,
        SnapshotStore $snapshotStore,
        AggregateReconstructor $aggregateReconstructor
    ) {
        parent::__construct(
            DummyEventSourcedAggregate::class,
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor
        );
    }
}
