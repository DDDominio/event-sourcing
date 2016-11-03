<?php

namespace Tests\EventStore\Common\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use EventStore\Common\Annotation\PublishDomainEvent;

class PublishDomainEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldReadPublishDomainEventAnnotation()
    {
        AnnotationRegistry::registerAutoloadNamespace(
            'EventStore\Common\Annotation',
            __DIR__ . '/../../../../src'
        );

        $reader = new AnnotationReader();

        $aggregateClass = new \ReflectionClass(Aggregate::class);

        $aggregateConstructorMethod = $aggregateClass->getMethod('__construct');

        $constructorAnnotation = $reader->getMethodAnnotation(
            $aggregateConstructorMethod,
            PublishDomainEvent::class
        );

        $this->assertInstanceOf(PublishDomainEvent::class, $constructorAnnotation);
        $this->assertEquals('AggregateAdded', $constructorAnnotation->event());

        $aggregateChangeNameMethod = $aggregateClass->getMethod('changeName');

        $changeNameAnnotation = $reader->getMethodAnnotation(
            $aggregateChangeNameMethod,
            PublishDomainEvent::class
        );

        $this->assertInstanceOf(PublishDomainEvent::class, $changeNameAnnotation);
        $this->assertEquals('AggregateNameChanged', $changeNameAnnotation->event());
    }
}