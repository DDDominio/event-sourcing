<?php

namespace tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\InMemorySnapshotStore;
use EventSourcing\Common\Model\Snapshot;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\DummySnapshot;

class InMemorySnapshotStoreTest extends \PHPUnit_Framework_TestCase
{
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
        $eventStore = new InMemorySnapshotStore(
            ['aggregateClass' => ['aggregateId' => [$snapshot, $lastSnapshot]]]
        );

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
        $eventStore = new InMemorySnapshotStore();

        $eventStore->addSnapshot($snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot('aggregateClass', 'aggregateId');
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
    }

    /**
     * @test
     */
    public function findSnapshotForEventVersion()
    {
        $snapshots = [
            DummyEventSourcedAggregate::class => ['id' => [
                new DummySnapshot('id', 'new name', 'description', 2),
                new DummySnapshot('id', 'another name', 'new description', 4),
            ]]
        ];
        $eventStore = new InMemorySnapshotStore($snapshots);

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

        $this->assertEquals(2, $snapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForAnotherEventVersion()
    {
        $snapshots = [
            DummyEventSourcedAggregate::class => ['id' => [
                new DummySnapshot('id', 'new name', 'description', 2),
                new DummySnapshot('id', 'another name', 'new description', 4),
            ]]
        ];
        $eventStore = new InMemorySnapshotStore($snapshots);

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
    }
}
