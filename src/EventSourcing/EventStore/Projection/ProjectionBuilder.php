<?php

namespace DDDominio\EventSourcing\EventStore\Projection;

use DDDominio\Common\EventInterface;
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
     * @var Projector
     */
    private $projector;

    /**
     * @param EventStoreInterface $eventStore
     */
    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
        $this->stateInitializer = function() {
            return new \stdClass();
        };
        $this->forEachStream = false;
        $this->projector = new Projector();
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
        $state = $this->initState();
        if ($this->forEachStream) {
            $streams = $this->eventStore->readAllStreams();
            foreach ($streams as $stream) {
                $state = $this->runStreamProjection($stream);
            }
        } else {
            $stream = $this->executeStreamEventsQuery();
            $state = $this->runStreamProjection($stream);
        }
        foreach ($this->projector->emittedEventsByStream() as $streamId => $events) {
            $this->eventStore->appendToStream($streamId, $events);
        }
        return $state;
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
        $state = $this->initState();
        foreach ($stream as $event) {
            /** @var EventInterface $event */
            if (isset($this->eventHandlers[get_class($event->data())])) {
                $this->eventHandlers[get_class($event->data())]($event->data(), $state, $this->projector);
            }
        }
        return $state;
    }

    /**
     * @return \stdClass
     */
    private function initState()
    {
        $stateInitializer = $this->stateInitializer;
        return $stateInitializer(new \stdClass());
    }
}
