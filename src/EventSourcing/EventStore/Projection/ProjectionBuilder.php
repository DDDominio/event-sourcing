<?php

namespace DDDominio\EventSourcing\EventStore\Projection;

use DDDominio\Common\EventInterface;
use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Common\EventStreamInterface;
use DDDominio\EventSourcing\EventStore\EventStoreInterface;

class ProjectionBuilder
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var callable
     */
    private $stateInitializer;

    /**
     * @var string
     */
    private $from;

    /**
     * @var bool
     */
    private $forEachStream;

    /**
     * @var array
     */
    private $eventHandlers;

    /**
     * @var array
     */
    private $emittedEvents;

    /**
     * @param EventStoreInterface $eventStore
     */
    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->emittedEvents = [];
        $this->stateInitializer = function() {
            return new \stdClass();
        };
        $this->forEachStream = false;
    }

    /**
     * @param callable $stateInitializer
     * @return $this
     */
    public function init(callable $stateInitializer)
    {
        $this->stateInitializer = $stateInitializer;
        return $this;
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
     * @return $this
     */
    public function fromAll()
    {
        $this->from('');
        return $this;
    }

    /**
     * @return $this
     */
    public function forEachStream()
    {
        $this->forEachStream = true;
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
     * @return \stdClass
     */
    public function execute()
    {
        $state = new \stdClass();
        if ($this->forEachStream) {
            $streams = $this->eventStore->readAllStreams();
            foreach ($streams as $stream) {
                $state = $this->runStreamProjection($stream);
            }
        } else {
            $stream = $this->executeStreamEventsQuery();
            $state = $this->runStreamProjection($stream);
        }
        foreach ($this->emittedEvents as $streamId => $events) {
            $this->eventStore->appendToStream($streamId, $events);
        }
        return $state;
    }

    /**
     * @param string $streamId
     * @param EventInterface $event
     */
    private function emit($streamId, $event)
    {
        $this->emittedEvents[$streamId][] = DomainEvent::record($event);
    }

    /**
     * @return \DDDominio\EventSourcing\Common\EventStreamInterface
     */
    private function executeStreamEventsQuery()
    {
        return empty($this->from) ?
            $stream = $this->eventStore->readAllEvents() :
            $stream = $this->eventStore->readFullStream($this->from);
    }

    /**
     * @param EventStreamInterface $stream
     * @return \stdClass
     */
    private function runStreamProjection($stream)
    {
        $state = new \stdClass();
        $stateInitializer = $this->stateInitializer;
        $stateInitializer($state);
        foreach ($stream as $event) {
            /** @var EventInterface $event */
            if (isset($this->eventHandlers[get_class($event->data())])) {
                $this->eventHandlers[get_class($event->data())]->call($this, $event->data(), $state);
            }
        }
        return $state;
    }
}
