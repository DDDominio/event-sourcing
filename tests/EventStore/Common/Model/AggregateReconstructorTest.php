<?php

namespace tests\EventStore\Common\Model;

use EventStore\Common\Model\AggregateReconstructor;
use EventStore\Common\Model\Snapshot;
use EventStore\Common\Model\Snapshotter;
use Tests\EventStore\Common\Model\TestData\DummyCreated;
use Tests\EventStore\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventStore\Common\Model\TestData\NameChanged;

class AggregateReconstructorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function reconstructAnAggregateFromOneEvent()
    {
        $snapshotter = $this->createMock(Snapshotter::class);
        $reconstructor = new AggregateReconstructor($snapshotter);
        $dummyCreatedEvent = new DummyCreated('name', 'description');
        $events = [$dummyCreatedEvent];

        $aggregate = $reconstructor->reconstitute(DummyEventSourcedAggregate::class, $events);

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
        $dummyCreatedEvent = new DummyCreated('name', 'description');
        $dummyNameChanged = new NameChanged('new name');

        $events = [$dummyCreatedEvent, $dummyNameChanged];

        $aggregate = $reconstructor->reconstitute(DummyEventSourcedAggregate::class, $events);

        $this->assertEquals(2, $aggregate->version());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function notEventSourcedAggregatCanNotBeReconstructed()
    {
        $snapshotter = $this->createMock(Snapshotter::class);
        $reconstructor = new AggregateReconstructor($snapshotter);

        $reconstructor->reconstitute(__CLASS__, []);
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
            [],
            $snapshot
        );

        $this->assertEquals(10, $reconstructedAggregate->version());
    }
}