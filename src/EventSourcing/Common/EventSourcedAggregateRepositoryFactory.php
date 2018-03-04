<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\EventSourcing\EventStore\EventStoreInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotStoreInterface;

class EventSourcedAggregateRepositoryFactory
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var SnapshotStoreInterface
     */
    private $snapshotStore;

    /**
     * @var AggregateReconstructor
     */
    private $aggregateReconstructor;

    /**
     * @var AggregateIdExtractorInterface
     */
    private $aggregateIdExtractor;

    /**
     * EventSourcedAggregateRepositoryFactory constructor.
     * @param EventStoreInterface $eventStore
     * @param SnapshotStoreInterface $snapshotStore
     * @param AggregateReconstructor $aggregateReconstructor
     * @param AggregateIdExtractorInterface $aggregateIdExtractor
     */
    public function __construct(EventStoreInterface $eventStore, SnapshotStoreInterface $snapshotStore, AggregateReconstructor $aggregateReconstructor, AggregateIdExtractorInterface $aggregateIdExtractor)
    {
        $this->eventStore = $eventStore;
        $this->snapshotStore = $snapshotStore;
        $this->aggregateReconstructor = $aggregateReconstructor;
        $this->aggregateIdExtractor = $aggregateIdExtractor;
    }

    /**
     * @param string $aggregateClass
     * @return EventSourcedAggregateRepository
     */
    public function build($aggregateClass)
    {
        return new EventSourcedAggregateRepository(
            $this->eventStore,
            $this->snapshotStore,
            $this->aggregateReconstructor,
            $this->aggregateIdExtractor,
            $aggregateClass
        );
    }
}