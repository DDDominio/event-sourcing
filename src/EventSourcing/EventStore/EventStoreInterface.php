<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\Common\EventInterface;
use DDDominio\EventSourcing\Common\EventStreamInterface;

interface EventStoreInterface
{
    const EXPECTED_VERSION_EMPTY_STREAM = 0;
    const EXPECTED_VERSION_ANY = -1;

    /**
     * @param string $streamId
     * @param EventInterface[] $events
     * @param int $expectedVersion
     * @throws ConcurrencyException
     * @throws EventStreamDoesNotExistException
     */
    public function appendToStream($streamId, $events, $expectedVersion = self::EXPECTED_VERSION_EMPTY_STREAM);

    /**
     * @param string $streamId
     * @param int $start
     * @param int $count
     * @return EventStreamInterface
     */
    public function readStreamEventsForward($streamId, $start = 1, $count = null);

    /**
     * @param string $streamId
     * @return EventStreamInterface
     */
    public function readFullStream($streamId);

    /**
     * @param $eventStoreEvent
     * @param EventStoreListenerInterface|callable $eventStoreListener
     */
    public function addEventListener($eventStoreEvent, $eventStoreListener);

    /**
     * @return EventStreamInterface[]
     */
    public function readAllStreams();

    /**
     * @return EventStreamInterface
     */
    public function readAllEvents();
}
