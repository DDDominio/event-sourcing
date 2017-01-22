<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\Common\Event;
use DDDominio\EventSourcing\Common\EventStreamInterface;

interface EventStore
{
    const AFTER_EVENTS_APPENDED = 'after_events_appended';

    const EXPECTED_VERSION_EMPTY_STREAM = 0;

    /**
     * @param string $streamId
     * @param Event[] $events
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
     * @param callable $callable
     */
    public function addEventListener($eventStoreEvent, callable $callable);
}
