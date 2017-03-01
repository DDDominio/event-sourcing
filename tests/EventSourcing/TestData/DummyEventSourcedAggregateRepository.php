<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\AggregateIdExtractorInterface;
use DDDominio\EventSourcing\Common\AggregateReconstructor;
use DDDominio\EventSourcing\Common\EventSourcedAggregateRepository;
use DDDominio\EventSourcing\EventStore\EventStoreInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotStoreInterface;

class DummyEventSourcedAggregateRepository extends EventSourcedAggregateRepository
{
    public function __construct(
        EventStoreInterface $eventStore,
        SnapshotStoreInterface $snapshotStore,
        AggregateReconstructor $aggregateReconstructor,
        AggregateIdExtractorInterface $aggregateIdExtractor
    ) {
        parent::__construct(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            $aggregateIdExtractor,
            DummyEventSourcedAggregate::class
        );
    }
}
