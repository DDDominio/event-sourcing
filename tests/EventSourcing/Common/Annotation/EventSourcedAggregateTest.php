<?php

namespace Tests\EventSourcing\Common\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use EventSourcing\Common\Annotation\EventSourcedAggregate;

class EventSourcedAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldReadEventSourcedAggregateAnnotation()
    {
        AnnotationRegistry::registerAutoloadNamespace('EventSourcing\Common\Annotation', __DIR__ . '/../../../../src');

        $reader = new AnnotationReader();

        $aggregateClass = new \ReflectionClass(Aggregate::class);

        $annotation = $reader->getClassAnnotation($aggregateClass, EventSourcedAggregate::class);

        $this->assertInstanceOf(EventSourcedAggregate::class, $annotation);
    }
}