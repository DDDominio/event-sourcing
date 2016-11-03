<?php

namespace EventSourcing\Common\Model;

use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;

class AggregateReconstructor
{
    /**
     * @var Snapshotter
     */
    private $snapshooter;

    /**
     * @param Snapshotter $snapshooter
     */
    public function __construct($snapshooter)
    {
        $this->snapshooter = $snapshooter;
    }

    /**
     * @param string $class
     * @param DomainEvent[] $events
     * @param Snapshot $snapshot
     * @return DummyEventSourcedAggregate
     */
    public function reconstitute($class, $events, $snapshot = null)
    {
        $this->assertValidClass($class);

        if (isset($snapshot)) {
            $aggregate = $this->snapshooter->translateSnapshot($snapshot);
        } else {
            $aggregate = $class::buildEmpty();
        }

        foreach ($events as $event) {
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
}