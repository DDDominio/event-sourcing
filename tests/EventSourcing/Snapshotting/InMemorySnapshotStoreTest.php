<?php

namespace DDDominio\Tests\EventSourcing\Snapshotting;

use DDDominio\EventSourcing\Snapshotting\InMemorySnapshotStore;
use DDDominio\EventSourcing\Snapshotting\Snapshot;
use DDDominio\Tests\EventSourcing\Common\TestData\DummyEventSourcedAggregate;
use DDDominio\Tests\EventSourcing\Common\TestData\DummySnapshot;

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
        $snapshotStore = new InMemorySnapshotStore(
            ['aggregateClass' => ['aggregateId' => [$snapshot, $lastSnapshot]]]
        );

        $retrievedSnapshot = $snapshotStore->findLastSnapshot('aggregateClass', 'aggregateId');

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
        $snapshotStore = new InMemorySnapshotStore();

        $snapshotStore->addSnapshot($snapshot);

        $retrievedSnapshot = $snapshotStore->findLastSnapshot('aggregateClass', 'aggregateId');
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
        $snapshotStore = new InMemorySnapshotStore($snapshots);

        $snapshot = $snapshotStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

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
        $snapshotStore = new InMemorySnapshotStore($snapshots);

        $snapshot = $snapshotStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
    }
}
