<?php

namespace DDDominio\Tests\EventSourcing\EventStore;

use DDDominio\EventSourcing\EventStore\StoredEvent;

class StoredEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function makeAnStoredEvent()
    {
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'type',
            'data',
            'metadata',
            new \DateTimeImmutable('2017-02-05 10:15:30'),
            1
        );

        $this->assertSame('id', $storedEvent->id());
        $this->assertSame('streamId', $storedEvent->streamId());
        $this->assertSame('type', $storedEvent->type());
        $this->assertSame('data', $storedEvent->data());
        $this->assertSame('metadata', $storedEvent->metadata());
        $this->assertSame('2017-02-05 10:15:30', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
        $this->assertSame(1, $storedEvent->version());
    }

    /**
     * @test
     */
    public function setStoredEventType()
    {
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'type',
            'data',
            'metadata',
            new \DateTimeImmutable('2017-02-05 10:15:30'),
            1
        );

        $storedEvent->setType('anotherType');

        $this->assertSame('id', $storedEvent->id());
        $this->assertSame('streamId', $storedEvent->streamId());
        $this->assertSame('anotherType', $storedEvent->type());
        $this->assertSame('data', $storedEvent->data());
        $this->assertSame('metadata', $storedEvent->metadata());
        $this->assertSame('2017-02-05 10:15:30', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
        $this->assertSame(1, $storedEvent->version());
    }

    /**
     * @test
     */
    public function setStoredEventData()
    {
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'type',
            'data',
            'metadata',
            new \DateTimeImmutable('2017-02-05 10:15:30'),
            1
        );

        $storedEvent->setData('new data');

        $this->assertSame('id', $storedEvent->id());
        $this->assertSame('streamId', $storedEvent->streamId());
        $this->assertSame('type', $storedEvent->type());
        $this->assertSame('new data', $storedEvent->data());
        $this->assertSame('metadata', $storedEvent->metadata());
        $this->assertSame('2017-02-05 10:15:30', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
        $this->assertSame(1, $storedEvent->version());
    }

    /**
     * @test
     */
    public function setStoredEventVersion()
    {
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'type',
            'data',
            'metadata',
            new \DateTimeImmutable('2017-02-05 10:15:30'),
            1
        );

        $storedEvent->setVersion(10);

        $this->assertSame('id', $storedEvent->id());
        $this->assertSame('streamId', $storedEvent->streamId());
        $this->assertSame('type', $storedEvent->type());
        $this->assertSame('data', $storedEvent->data());
        $this->assertSame('metadata', $storedEvent->metadata());
        $this->assertSame('2017-02-05 10:15:30', $storedEvent->occurredOn()->format('Y-m-d H:i:s'));
        $this->assertSame(10, $storedEvent->version());
    }
}
