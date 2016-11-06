<?php

namespace EventSourcing\Common\Model;

class InMemoryEventStore implements EventStore
{
    /**
     * @var array
     */
    private $streams;

    /**
     * @param array $streams
     */
    public function __construct(array $streams = [])
    {
        $this->streams = $streams;
    }

    /**
     * @param string $streamId
     * @param DomainEvent[] $domainEvents
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $domainEvents, $expectedVersion = null)
    {
        if (isset($this->streams[$streamId])) {
            $this->assertOptimisticConcurrency($streamId, $expectedVersion);
            $this->streams[$streamId] = $this->streams[$streamId]->append($domainEvents);
        } else {
            $this->assertEventStreamExistence($expectedVersion);
            $this->streams[$streamId] = new EventStream($domainEvents);
        }
    }

    /**
     * @param string $streamId
     * @return EventStream
     */
    public function readFullStream($streamId)
    {
        return isset($this->streams[$streamId]) ?
            $this->streams[$streamId] : EventStream::buildEmpty();
    }

    /**
     * @param $expectedVersion
     * @throws EventStreamDoesNotExistException
     */
    private function assertEventStreamExistence($expectedVersion)
    {
        if (isset($expectedVersion)) {
            throw new EventStreamDoesNotExistException();
        }
    }

    /**
     * @param $streamId
     * @param $expectedVersion
     * @throws ConcurrencyException
     */
    private function assertOptimisticConcurrency($streamId, $expectedVersion)
    {
        if (count($this->streams[$streamId]->events()) !== $expectedVersion) {
            throw new ConcurrencyException();
        }
    }
}
