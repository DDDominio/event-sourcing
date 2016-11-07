<?php

namespace tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\DomainEvent;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\InMemoryEventStore;
use EventSourcing\Common\Model\Snapshot;

class InMemoryEventStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $eventStore = new InMemoryEventStore();
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('streamId', [$domainEvent]);
        $stream = $eventStore->readFullStream('streamId');

        $this->assertInstanceOf(EventStream::class, $stream);
        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function appendAnEventToAnExistentStream()
    {
        $eventStore = new InMemoryEventStore();
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('streamId', [$domainEvent]);
        $eventStore->appendToStream('streamId', [$domainEvent], 1);
        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(2, $stream);
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\ConcurrencyException
     */
    public function ifTheExpectedVersionOfTheStreamDoesNotMatchWithRealVersionAConcurrencyExceptionShouldBeThrown()
    {
        $domainEvent = $this->createMock(DomainEvent::class);
        // expected version form streamId: 1
        $eventStore = new InMemoryEventStore([
            'streamId' => new EventStream([$domainEvent])
        ]);

        $eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $eventStore = new InMemoryEventStore();
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $event = $this->createMock(DomainEvent::class);
        $eventStore = new InMemoryEventStore([
            'streamId' => new EventStream([$event])
        ]);

        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $eventStore = new InMemoryEventStore();

        $stream = $eventStore->readFullStream('NonExistentStreamId');

        $this->assertTrue($stream->isEmpty());
        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function findLastSnapshotOfAStream()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $lastSnapshot = $this->createMock(Snapshot::class);
        $lastSnapshot
            ->method('aggregateClass')
            ->willReturn('lastSnapshot');
        $eventStore = new InMemoryEventStore([], [
            'streamId' => [$snapshot, $lastSnapshot]
        ]);

        $retrievedSnapshot = $eventStore->findLastSnapshot('streamId');

        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals('lastSnapshot', $retrievedSnapshot->aggregateClass());
    }

    /**
     * @test
     */
    public function addAnSnapshot()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $eventStore = new InMemoryEventStore();

        $eventStore->addSnapshot('streamId', $snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot('streamId');
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
    }
}
