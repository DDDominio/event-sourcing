<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\EventStore\EventStoreListenerInterface;

class RecorderEventListener implements EventStoreListenerInterface
{
    /**
     * @var mixed
     */
    private $recordedEvents = [];

    /**
     * @param DomainEvent[] $events
     */
    public function handle($events)
    {
        foreach ($events as $event) {
            $this->recordedEvents[] = $event->data();
        }
    }

    /**
     * @return mixed
     */
    public function recordedEvents()
    {
        return $this->recordedEvents;
    }
}
