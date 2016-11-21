<?php

namespace tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\DomainEvent;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\InMemoryEventStore;
use EventSourcing\Common\Model\Snapshot;
use Tests\EventSourcing\Common\Model\TestData\DescriptionChanged;
use Tests\EventSourcing\Common\Model\TestData\DummyCreated;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\DummySnapshot;
use Tests\EventSourcing\Common\Model\TestData\NameChanged;

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
            ->willReturn('aggregateClass');
        $lastSnapshot
            ->method('aggregateId')
            ->willReturn('aggregateId');
        $eventStore = new InMemoryEventStore([], [
            'aggregateClass' => ['aggregateId' => [$snapshot, $lastSnapshot]]
        ]);

        $retrievedSnapshot = $eventStore->findLastSnapshot('aggregateClass', 'aggregateId');

        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals('aggregateClass', $retrievedSnapshot->aggregateClass());
        $this->assertEquals('aggregateId', $retrievedSnapshot->aggregateId());
    }

    /**
     * @test
     */
    public function addAnSnapshot()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $snapshot
            ->method('aggregateClass')
            ->willReturn('aggregateClass');
        $snapshot
            ->method('aggregateId')
            ->willReturn('aggregateId');
        $eventStore = new InMemoryEventStore();

        $eventStore->addSnapshot($snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot('aggregateClass', 'aggregateId');
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
    }

    /**
     * @test
     */
    public function findStreamEventsForward()
    {
        $eventStore = new InMemoryEventStore([
            'streamId' => [
                new NameChanged('new name'),
                new DescriptionChanged('new description'),
                new NameChanged('another name'),
                new NameChanged('my name'),
            ]
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 2);

        $this->assertCount(3, $stream);
        $events = $stream->events();
        $this->assertEquals('new description', $events[0]->description());
        $this->assertEquals('another name', $events[1]->name());
        $this->assertEquals('my name', $events[2]->name());
    }

    /**
     * @test
     */
    public function findStreamEventsForwardWithEventCount()
    {
        $eventStore = new InMemoryEventStore([
            'streamId' => [
                new NameChanged('new name'),
                new DescriptionChanged('new description'),
                new NameChanged('another name'),
                new NameChanged('my name'),
            ]
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 2, 2);

        $this->assertCount(2, $stream);
        $events = $stream->events();
        $this->assertEquals('new description', $events[0]->description());
        $this->assertEquals('another name', $events[1]->name());
    }

    /**
     * @test
     */
    public function findStreamEventsForwardShouldReturnEmptyStreamIfStartVersionIsGreaterThanStreamVersion()
    {
        $eventStore = new InMemoryEventStore([
            'streamId' => [
                new NameChanged('new name'),
                new DescriptionChanged('new description'),
                new NameChanged('another name'),
                new NameChanged('my name'),
            ]
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 5);

        $this->assertTrue($stream->isEmpty());
    }

    /**
     * @test
     */
    public function findSnapshotForEventVersion()
    {
        $streams = [
            'streamId' => [
                new DummyCreated('id', 'name', 'description'),
                new NameChanged('new name'),
                new DescriptionChanged('new description'),
                new NameChanged('another name'),
                new NameChanged('my name'),
            ]
        ];
        $snapshots = [
            DummyEventSourcedAggregate::class => ['id' => [
                new DummySnapshot('id', 'new name', 'description', 2),
                new DummySnapshot('id', 'another name', 'new description', 4),
            ]]
        ];
        $eventStore = new InMemoryEventStore($streams, $snapshots);

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

        $this->assertEquals(2, $snapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForAnotherEventVersion()
    {
        $streams = [
            'streamId' => [
                new DummyCreated('id', 'name', 'description'),
                new NameChanged('new name'),
                new DescriptionChanged('new description'),
                new NameChanged('another name'),
                new NameChanged('my name'),
            ]
        ];
        $snapshots = [
            DummyEventSourcedAggregate::class => ['id' => [
                new DummySnapshot('id', 'new name', 'description', 2),
                new DummySnapshot('id', 'another name', 'new description', 4),
            ]]
        ];
        $eventStore = new InMemoryEventStore($streams, $snapshots);

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
    }
}
