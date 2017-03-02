<?php

namespace Tests\EventSourcing\EventStore;

use DDDominio\EventSourcing\EventStore\EventStoreEvents;

class EventStoreEventsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function eventStoreEventsCannotBeInitialized()
    {
        $reflectedClass = new \ReflectionClass(EventStoreEvents::class);
        $constructor = $reflectedClass->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke($reflectedClass->newInstanceWithoutConstructor());

        $this->assertTrue($constructor->isPrivate());
    }
}
