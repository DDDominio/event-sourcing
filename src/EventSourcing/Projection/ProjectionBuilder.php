<?php

namespace DDDominio\EventSourcing\Projection;

use Common\Event;
use DDDominio\EventSourcing\Common\EventStore;

class ProjectionBuilder
{
    /**
     * @var EventStore
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
     * @var Event[]
     */
    private $emittedEvents;

    /**
     * @param EventStore $eventStore
     */
    public function __construct(EventStore $eventStore)
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
            if (isset($this->eventHandlers[get_class($event)])) {
                $this->eventHandlers[get_class($event)]->call($this, $event);
            }
        }
        $this->eventStore->appendToStream($streamId, $this->emittedEvents);
    }

    /**
     * @param Event $event
     */
    private function emit($event)
    {
        $this->emittedEvents[] = $event;
    }
}
