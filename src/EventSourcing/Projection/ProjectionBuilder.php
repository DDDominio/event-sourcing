<?php

namespace DDDominio\EventSourcing\Projection;

use DDDominio\Common\EventInterface;
use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\EventStore\EventStoreInterface;

class ProjectionBuilder
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var string
     */
    private $from;

    /**
     * @var array
     */
    private $eventHandlers;

    /**
     * @var EventInterface[]
     */
    private $emittedEvents;

    /**
     * @param EventStoreInterface $eventStore
     */
    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @param string $streamId
     * @return $this
     */
    public function from($streamId)
    {
        $this->from = $streamId;
        return $this;
    }

    /**
     * @param string $eventClass
     * @param callable $eventHandler
     * @return $this
     */
    public function when($eventClass, callable $eventHandler)
    {
        $this->eventHandlers[$eventClass] = $eventHandler;
        return $this;
    }

    /**
     * @param string $streamId
     */
    public function execute($streamId)
    {
        $stream = $this->eventStore->readFullStream($this->from);
        foreach ($stream as $event) {
            /** @var EventInterface $event */
            if (isset($this->eventHandlers[get_class($event->data())])) {
                $this->eventHandlers[get_class($event->data())]->call($this, $event->data());
            }
        }
        $this->eventStore->appendToStream($streamId, $this->emittedEvents);
    }

    /**
     * @param EventInterface $event
     */
    private function emit($event)
    {
        $this->emittedEvents[] = DomainEvent::record($event);
    }
}
