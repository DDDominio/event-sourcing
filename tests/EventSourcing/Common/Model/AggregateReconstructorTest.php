<?php

namespace Tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\AggregateReconstructor;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\Snapshot;
use EventSourcing\Common\Model\Snapshotter;
use Tests\EventSourcing\Common\Model\TestData\DummyCreated;
use Tests\EventSourcing\Common\Model\TestData\DummyDeleted;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\DummyReflectionSnapshotTranslator;
use Tests\EventSourcing\Common\Model\TestData\DummySnapshot;
use Tests\EventSourcing\Common\Model\TestData\NameChanged;

class AggregateReconstructorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function reconstructAnAggregateFromOneEvent()
    {
        $snapshotter = $this->createMock(Snapshotter::class);
        $reconstructor = new AggregateReconstructor($snapshotter);
        $dummyCreatedEvent = new DummyCreated('id', 'name', 'description');
        $eventStream = new EventStream([$dummyCreatedEvent]);

        $aggregate = $reconstructor->reconstitute(DummyEventSourcedAggregate::class, $eventStream);

        $this->assertEquals(1, $aggregate->version());
        $this->assertEquals('name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     */
    public function reconstructAnAggregateFromMultipleEvents()
    {
        $snapshotter = $this->createMock(Snapshotter::class);
        $reconstructor = new AggregateReconstructor($snapshotter);
        $dummyCreatedEvent = new DummyCreated('id', 'name', 'description');
        $dummyNameChanged = new NameChanged('new name');
        $eventStream = new EventStream([$dummyCreatedEvent, $dummyNameChanged]);

        $aggregate = $reconstructor->reconstitute(DummyEventSourcedAggregate::class, $eventStream);

        $this->assertEquals(2, $aggregate->version());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     */
    public function reconstructAnAggregateUsingAnSnapshot()
    {
        $snapshotTranslator = new DummyReflectionSnapshotTranslator();
        $snapshotter = new Snapshotter();
        $snapshotter->addSnapshotTranslator(
            DummyEventSourcedAggregate::class,
            $snapshotTranslator
        );
        $reconstructor = new AggregateReconstructor($snapshotter);
        $snapshot = new DummySnapshot('id', 'name', 'description', 2);
        $eventStream = new EventStream([new NameChanged('new name')]);

        $aggregate = $reconstructor->reconstitute(
            DummyEventSourcedAggregate::class,
            $eventStream,
            $snapshot
        );

        $this->assertEquals(3, $aggregate->version());
        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function notEventSourcedAggregateCanNotBeReconstructed()
    {
        $snapshotter = $this->createMock(Snapshotter::class);
        $reconstructor = new AggregateReconstructor($snapshotter);

        $reconstructor->reconstitute(__CLASS__, new EventStream([]));
    }

    /**
     * @test
     */
    public function reconstructUsingSnapshot()
    {
        $snapshot = $this->createMock(Snapshot::class);
        $aggregateMock = $this->createMock(DummyEventSourcedAggregate::class);
        $aggregateMock
            ->method('version')
            ->willReturn(10);
        $snapshooter = $this->createMock(Snapshotter::class);
        $snapshooter
            ->method('translateSnapshot')
            ->willReturn($aggregateMock);
        $reconstructor = new AggregateReconstructor($snapshooter);

        $reconstructedAggregate = $reconstructor->reconstitute(
            DummyEventSourcedAggregate::class,
            new EventStream([]),
            $snapshot
        );

        $this->assertEquals(10, $reconstructedAggregate->version());
    }

    /**
     * @test
     */
    public function whenLastEventIsAnAggregateDeleterItShouldReturnNull()
    {
        $snapshotter = $this->createMock(Snapshotter::class);
        $reconstructor = new AggregateReconstructor($snapshotter);
        $eventStream = new EventStream([
            new DummyCreated('id', 'name', 'description'),
            new DummyDeleted('id')
        ]);

        $aggregate = $reconstructor->reconstitute(DummyEventSourcedAggregate::class, $eventStream);

        $this->assertNull($aggregate);
    }
}
