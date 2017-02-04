<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\AggregateReconstructor;
use DDDominio\EventSourcing\Common\EventSourcedAggregateRepository;
use DDDominio\EventSourcing\EventStore\EventStoreInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotStoreInterface;

class DummyEventSourcedAggregateRepository extends EventSourcedAggregateRepository
{
    public function __construct(
        EventStoreInterface $eventStore,
        SnapshotStoreInterface $snapshotStore,
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
