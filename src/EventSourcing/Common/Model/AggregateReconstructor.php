<?php

namespace EventSourcing\Common\Model;

class AggregateReconstructor
{
    /**
     * @var Snapshotter
     */
    private $snapshooter;

    /**
     * @param Snapshotter $snapshotter
     */
    public function __construct($snapshotter)
    {
        $this->snapshooter = $snapshotter;
    }

    /**
     * @param string $class
     * @param EventStream $eventStream
     * @param Snapshot $snapshot
     * @return EventSourcedAggregate
     */
    public function reconstitute($class, $eventStream, $snapshot = null)
    {
        $this->assertValidClass($class);

        if (is_null($snapshot) && $eventStream->isEmpty()) {
            return null;
        }

        $events = $eventStream->events();
        $lastEvent = end($events);
        if ($lastEvent instanceof AggregateDeleter) {
            return null;
        }

        if ($snapshot instanceof Snapshot) {
            $aggregate = $this->snapshooter->translateSnapshot($snapshot);
        } else {
            $aggregate = $this->buildEmptyAggregate($class);
        }

        foreach ($eventStream as $event) {
            $aggregate->apply($event, false);
        }

        return $aggregate;
    }

    /**
     * @param string $class
     */
    private function assertValidClass($class)
    {
        $reflectedClass = new \ReflectionClass($class);

        $traitNames = $reflectedClass->getTraitNames();

        if (!in_array(EventSourcedAggregate::class, $traitNames)) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @param string $class
     * @return EventSourcedAggregate
     */
    private function buildEmptyAggregate($class)
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}