<?php

namespace tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\Snapshot;
use EventSourcing\Common\Model\ReflectionSnapshotTranslator;
use EventSourcing\Common\Model\Snapshotter;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;

class SnapshotterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function buildAnSnapshotFromAggregateCooperatesWithSnapshotStrategy()
    {
        $snapshotStub = $this->createMock(Snapshot::class);
        $aggregate = $this->createMock(DummyEventSourcedAggregate::class);
        $snapshotStrategyMock = $this->makeSnapshotStrategyMock();
        $snapshotStrategyMock->expects($this->once())
            ->method('buildSnapshotFromAggregate')
            ->with($aggregate)
            ->willReturn($snapshotStub);
        $snapshotter = $this->makeSnapshotter([get_class($aggregate) => $snapshotStrategyMock]);

        $snapshot = $snapshotter->takeSnapshot($aggregate);

        $this->assertInstanceOf(Snapshot::class, $snapshot);
    }

    /**
     * @test
     */
    public function buildAnAggregateFromSnapshotCooperatesWithSnapshotStrategyAndSnapshot()
    {
        $snapshot = $this->getMockBuilder(Snapshot::class)
            ->setMethods(['aggregateClass', 'aggregateId', 'version'])
            ->getMock();
        $snapshot
            ->expects($this->once())
            ->method('aggregateClass')
            ->willReturn(DummyEventSourcedAggregate::class);
        $aggregate = $this->createMock(DummyEventSourcedAggregate::class);
        $snapshotStrategyMock = $this->makeSnapshotStrategyMock();
        $snapshotStrategyMock->expects($this->once())
            ->method('buildAggregateFromSnapshot')
            ->with($snapshot)
            ->willReturn($aggregate);
        $snapshotter = $this->makeSnapshotter([DummyEventSourcedAggregate::class => $snapshotStrategyMock]);

        $aggregate = $snapshotter->translateSnapshot($snapshot);

        $this->assertInstanceOf(DummyEventSourcedAggregate::class, $aggregate);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function makeSnapshotStrategyMock()
    {
        return $this->getMockBuilder(ReflectionSnapshotTranslator::class)
            ->setMethods([
                'buildSnapshotFromAggregate',
                'buildAggregateFromSnapshot',
                'aggregateClass',
                'snapshotClass',
                'aggregateToSnapshotPropertyDictionary'
            ])
            ->getMock();
    }

    /**
     * @param array $strategies
     * @return Snapshotter
     */
    private function makeSnapshotter($strategies)
    {
        $snapshotter = new Snapshotter();
        foreach ($strategies as $aggregateClass => $strategy) {
            $snapshotter->addSnapshotTranslator($aggregateClass, $strategy);
        }
        return $snapshotter;
    }
}
