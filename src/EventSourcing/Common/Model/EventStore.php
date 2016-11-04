<?php

namespace EventSourcing\Common\Model;

interface EventStore
{
    /**
     * @param string $streamId
     * @param DomainEvent[] $domainEvents
     * @param int $originalVersion
     * @throws ConcurrencyException
     */
    public function appendToStream($streamId, $domainEvents, $originalVersion);
}
