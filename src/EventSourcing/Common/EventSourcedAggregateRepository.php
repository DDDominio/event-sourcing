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
     * @var SnapshotStoreInterface
     */
    private $snapshotStore;

    /**
     * @var AggregateReconstructor
     */
    private $aggregateReconstructor;

    /**
     * @var string
     */
    private $aggregateClass;

    /**
     * @var AggregateIdExtractorInterface
     */
    private $aggregateIdExtractor;

    /**
     * @param EventStoreInterface $eventStore
     * @param SnapshotStoreInterface $snapshotStore
     * @param AggregateReconstructor $aggregateReconstructor
     * @param AggregateIdExtractorInterface $aggregateIdExtractor
     * @param string $aggregateClass
     */
    public function __construct(
        EventStoreInterface $eventStore,
        SnapshotStoreInterface $snapshotStore,
        $aggregateReconstructor,
        $aggregateIdExtractor,
        $aggregateClass
    ) {
        $this->eventStore = $eventStore;
        $this->snapshotStore = $snapshotStore;
        $this->aggregateReconstructor = $aggregateReconstructor;
        $this->aggregateIdExtractor = $aggregateIdExtractor;
        $this->aggregateClass = $aggregateClass;
    }

    /**
     * @param EventSourcedAggregateRootInterface $aggregate
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
        $snapshot = $this->snapshotStore
            ->findLastSnapshot($this->aggregateClass, $id);

        $streamId = $this->streamIdFromAggregateId($id);
        if ($snapshot) {
            $stream = $this->eventStore
                ->readStreamEvents($streamId, $snapshot->version() + 1);
        } else {
            $stream = $this->eventStore->readFullStream($streamId);
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
        $snapshot = $this->snapshotStore
            ->findNearestSnapshotToVersion($this->aggregateClass, $id, $version);

        $streamId = $this->streamIdFromAggregateId($id);
        if ($snapshot) {
            $stream = $this->eventStore
                ->readStreamEvents(
                    $streamId,
                    $snapshot->version() + 1,
                    $version - $snapshot->version()
                );
        } else {
            $stream = $this->eventStore
                ->readStreamEvents(
                    $streamId,
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
