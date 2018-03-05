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
     * @var AggregateReconstructor
     */
    private $aggregateReconstructor;

    /**
     * @var AggregateIdExtractorInterface
     */
    private $aggregateIdExtractor;

    /**
     * @var SnapshotStoreInterface
     */
    private $snapshotStore;

    /**
     * EventSourcedAggregateRepositoryFactory constructor.
     * @param EventStoreInterface $eventStore
     * @param AggregateReconstructor $aggregateReconstructor
     * @param AggregateIdExtractorInterface $aggregateIdExtractor
     * @param SnapshotStoreInterface|null $snapshotStore
     */
    public function __construct(EventStoreInterface $eventStore, AggregateReconstructor $aggregateReconstructor, AggregateIdExtractorInterface $aggregateIdExtractor, SnapshotStoreInterface $snapshotStore = null)
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
            $this->aggregateReconstructor,
            $this->aggregateIdExtractor,
            $aggregateClass,
            $this->snapshotStore
        );
    }
}