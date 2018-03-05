<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\EventSourcing\EventStore\EventStoreInterface;
use DDDominio\EventSourcing\Snapshotting\SnapshotStoreInterface;

class EventSourcedAggregateRepository
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
     * @var string
     */
    private $aggregateClass;

    /**
     * @var SnapshotStoreInterface|null
     */
    private $snapshotStore;

    /**
     * @param EventStoreInterface $eventStore
     * @param AggregateReconstructor $aggregateReconstructor
     * @param AggregateIdExtractorInterface $aggregateIdExtractor
     * @param string $aggregateClass
     * @param SnapshotStoreInterface|null $snapshotStore
     */
    public function __construct(
        EventStoreInterface $eventStore,
        AggregateReconstructor $aggregateReconstructor,
        AggregateIdExtractorInterface $aggregateIdExtractor,
        $aggregateClass,
        SnapshotStoreInterface $snapshotStore = null
    ) {
        $this->eventStore = $eventStore;
        $this->snapshotStore = $snapshotStore;
        $this->aggregateReconstructor = $aggregateReconstructor;
        $this->aggregateIdExtractor = $aggregateIdExtractor;
        $this->aggregateClass = $aggregateClass;
    }

    /**
     * @param EventSourcedAggregateRootInterface $aggregate
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function save($aggregate)
    {
        $this->assertValidAggregate($aggregate);
        $this->eventStore->appendToStream(
            $this->streamIdFromAggregate($aggregate),
            $aggregate->changes()->events(),
            $aggregate->originalVersion()
        );
        $aggregate->clearChanges();
    }

    /**
     * @param $aggregate
     */
    private function assertValidAggregate($aggregate)
    {
        if (is_null($aggregate) || !is_object($aggregate)) {

            throw new \InvalidArgumentException(
                sprintf('EventSourcedAggregateRepository expects an aggregate of type "%s" but "%s" given', $this->aggregateClass, gettype($aggregate))
            );
        }

        if (!($aggregate instanceof $this->aggregateClass)) {
            throw new \InvalidArgumentException(
                sprintf('EventSourcedAggregateRepository expects an aggregate of type "%s" but "%s" given', $this->aggregateClass, get_class($aggregate))
            );
        }
    }

    /**
     * @param string $id
     * @return EventSourcedAggregateRootInterface
     */
    public function findById($id)
    {
        $snapshot = null;

        if ($this->snapshotStore) {
            $snapshot = $this->snapshotStore->findLastSnapshot($this->aggregateClass, $id);
        }

        if ($snapshot) {
            $stream = $this->eventStore->readStreamEvents($this->streamIdFromAggregateId($id), $snapshot->version() + 1);
        } else {
            $stream = $this->eventStore->readFullStream($this->streamIdFromAggregateId($id));
        }

        return $this->aggregateReconstructor->reconstitute(
            $this->aggregateClass,
            $stream,
            $snapshot
        );
    }

    /**
     * @param string $id
     * @param int $version
     * @return EventSourcedAggregateRootInterface
     */
    public function findByIdAndVersion($id, $version)
    {
        $snapshot = null;

        if ($this->snapshotStore) {
            $snapshot = $this->snapshotStore->findNearestSnapshotToVersion($this->aggregateClass, $id, $version);
        }

        if ($snapshot) {
            $stream = $this->eventStore->readStreamEvents(
                $this->streamIdFromAggregateId($id),
                $snapshot->version() + 1,
                $version - $snapshot->version()
            );
        } else {
            $stream = $this->eventStore->readStreamEvents(
                $this->streamIdFromAggregateId($id),
                1,
                $version
            );
        }
        return $this->aggregateReconstructor->reconstitute(
            $this->aggregateClass,
            $stream,
            $snapshot
        );
    }

    /**
     * @param string $id
     * @param \DateTimeImmutable $datetime
     * @return EventSourcedAggregateRootInterface
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findByIdAndDatetime($id, $datetime)
    {
        $streamId = $this->streamIdFromAggregateId($id);
        $version = $this->eventStore->getStreamVersionAt($streamId, $datetime);
        return $this->findByIdAndVersion($id, $version);
    }

    /**
     * @param EventSourcedAggregateRootInterface $aggregate
     * @return string
     */
    private function streamIdFromAggregate($aggregate)
    {
        return $this->streamIdFromAggregateId(
            $this->aggregateIdExtractor->extract($aggregate)
        );
    }

    /**
     * @param string $aggregateId
     * @return string
     */
    protected function streamIdFromAggregateId($aggregateId)
    {
        return $this->aggregateClass . '-' . $aggregateId;
    }
}
