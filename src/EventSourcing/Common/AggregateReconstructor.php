<?php

namespace DDDominio\EventSourcing\Common;

use DDDominio\EventSourcing\Common\Annotation\AggregateDeleter;
use DDDominio\EventSourcing\Snapshotting\SnapshotInterface;
use DDDominio\EventSourcing\Snapshotting\Snapshotter;
use Doctrine\Common\Annotations\AnnotationReader;

class AggregateReconstructor
{
    /**
     * @var Snapshotter
     */
    private $snapshooter;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @param Snapshotter $snapshotter
     */
    public function __construct($snapshotter)
    {
        $this->snapshooter = $snapshotter;
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param string $class
     * @param EventStreamInterface $eventStream
     * @param SnapshotInterface $snapshot
     * @return EventSourcedAggregateRoot
     */
    public function reconstitute($class, $eventStream, $snapshot = null)
    {
        $this->assertValidClass($class);

        if (is_null($snapshot) && $eventStream->isEmpty()) {
            return null;
        }

        $events = $eventStream->events();
        if (!$eventStream->isEmpty()) {
            $lastEvent = end($events);
            $aggregateDeleterAnnotation = $this->annotationReader->getClassAnnotation(
                new \ReflectionClass(get_class($lastEvent)),
                AggregateDeleter::class
            );
            if (!is_null($aggregateDeleterAnnotation)) {
                return null;
            }
        }

        if ($snapshot instanceof SnapshotInterface) {
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

        if (!in_array(EventSourcedAggregateRoot::class, $traitNames)) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @param string $class
     * @return EventSourcedAggregateRoot
     */
    private function buildEmptyAggregate($class)
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}