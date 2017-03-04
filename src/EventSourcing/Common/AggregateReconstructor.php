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
     * @return EventSourcedAggregateRootInterface
     */
    public function reconstitute($class, $eventStream, $snapshot = null)
    {
        $this->assertValidClass($class);

        if (is_null($snapshot) && $eventStream->isEmpty()) {
            return null;
        }

        if (!$eventStream->isEmpty()) {
            $aggregateDeleterAnnotation = $this->annotationReader->getClassAnnotation(
                new \ReflectionClass(get_class($eventStream->last())),
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
            $aggregate->apply($event);
        }

        return $aggregate;
    }

    /**
     * @param string $class
     */
    private function assertValidClass($class)
    {
        $reflectedClass = new \ReflectionClass($class);

        $parentClass = $reflectedClass->getParentClass();

        if (EventSourcedAggregateRoot::class !== $parentClass->getName()) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * @param string $class
     * @return EventSourcedAggregateRootInterface
     */
    private function buildEmptyAggregate($class)
    {
        return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
