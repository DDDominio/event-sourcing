<?php

namespace DDDominio\EventSourcing\EventStore;

use DDDominio\EventSourcing\Common\DomainEvent;

interface EventStoreListenerInterface
{
    /**
     * @param DomainEvent[] $events
     */
    public function handle($events);
}
