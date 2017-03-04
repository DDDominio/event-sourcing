<?php

namespace Tests\EventSourcing\Common;

use DDDominio\Common\EventInterface;
use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Common\EventStream;

class EventStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function makeAndEmptyStream()
    {
        $stream = EventStream::buildEmpty();

        $this->assertCount(0, $stream);
        $this->assertTrue($stream->isEmpty());
    }

    /**
     * @test
     */
    public function makeANonEmptyStream()
    {
        $stream = new EventStream([
            DomainEvent::produceNow('data1'),
            DomainEvent::produceNow('data2')
        ]);

        $this->assertCount(2, $stream);
        $this->assertFalse($stream->isEmpty());
        $this->assertEquals('data1', $stream->get(0)->data());
        $this->assertEquals('data2', $stream->get(1)->data());
    }

    /**
     * @test
     */
    public function appendAnEventMakesAnotherStreamWithAppendedEvent()
    {
        $stream = EventStream::buildEmpty();

        $newStream = $stream->append(DomainEvent::produceNow('data'));

        $this->assertCount(0, $stream);
        $this->assertCount(1, $newStream);
        $this->assertEquals('data', $newStream->get(0)->data());
    }

    /**
     * @test
     */
    public function getLastEventOfTheStream()
    {
        $stream = new EventStream([
            DomainEvent::produceNow('data1'),
            DomainEvent::produceNow('data2'),
            DomainEvent::produceNow('data3')
        ]);

        $lastEvent = $stream->last();

        $this->assertInstanceOf(EventInterface::class, $lastEvent);
        $this->assertEquals('data3', $lastEvent->data());
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function getLastEventOfAnEmptyStreamThrowsAnException()
    {
        $stream = EventStream::buildEmpty();

        $stream->last();
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function requestANonExistentEventFromTheLowerLimit()
    {
        $stream = new EventStream([DomainEvent::produceNow('data')]);

        $stream->get(-1);
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function requestANonExistentEventFromTheUpperLimit()
    {
        $stream = new EventStream([DomainEvent::produceNow('data')]);

        $stream->get(1);
    }

    /**
     * @test
     */
    public function filterEventStream()
    {
        $stream = new EventStream([
            DomainEvent::produceNow('data1'),
            DomainEvent::produceNow('data2'),
            DomainEvent::produceNow('data3')
        ]);

        $filteredStream = $stream->filter(function(EventInterface $event) {
           return $event->data() === 'data2';
        });

        $this->assertCount(3, $stream);
        $this->assertCount(1, $filteredStream);
        $this->assertEquals('data2', $filteredStream->get(0)->data());
    }

    /**
     * @test
     */
    public function sliceEventStream()
    {
        $stream = new EventStream([
            DomainEvent::produceNow('data1'),
            DomainEvent::produceNow('data2'),
            DomainEvent::produceNow('data3')
        ]);

        $filteredStream = $stream->slice(1, 2);

        $this->assertCount(3, $stream);
        $this->assertCount(2, $filteredStream);
        $this->assertEquals('data2', $filteredStream->get(0)->data());
        $this->assertEquals('data3', $filteredStream->get(1)->data());
    }

    /**
     * @test
     */
    public function mapEventStream()
    {
        $stream = new EventStream([
            DomainEvent::produceNow('data1'),
            DomainEvent::produceNow('data2'),
            DomainEvent::produceNow('data3')
        ]);

        $filteredStream = $stream->map(function(EventInterface $event) {
            return DomainEvent::produceNow($event->data().'-suffix');
        });

        $this->assertCount(3, $stream);
        $this->assertEquals('data1', $stream->get(0)->data());
        $this->assertEquals('data2', $stream->get(1)->data());
        $this->assertEquals('data3', $stream->get(2)->data());
        $this->assertCount(3, $filteredStream);
        $this->assertEquals('data1-suffix', $filteredStream->get(0)->data());
        $this->assertEquals('data2-suffix', $filteredStream->get(1)->data());
        $this->assertEquals('data3-suffix', $filteredStream->get(2)->data());
    }
}
