<?php

namespace Tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\AggregateReconstructor;
use EventSourcing\Common\Model\EventSourcedAggregate;
use EventSourcing\Common\Model\DomainEvent;
use EventSourcing\Common\Model\EventSourcedAggregateRepository;
use EventSourcing\Common\Model\EventStore;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\InMemoryEventStore;
use Tests\EventSourcing\Common\Model\TestData\DummyCreated;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\NameChanged;

class EventSourcedAggregateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function addAnAggregate()
    {
        $eventStore = new InMemoryEventStore();
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $aggregate = $this->buildAggregateMock('id', $changes);

        $repository->add($aggregate);

        $stream = $eventStore->readFullStream('id');
        $this->assertCount(3, $stream->events());
    }

    /**
     * @test
     */
    public function addAnotherAggregate()
    {
        $eventStore = new InMemoryEventStore();
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $aggregate = $this->buildAggregateMock('anotherId', $changes);

        $repository->add($aggregate);

        $stream = $eventStore->readFullStream('anotherId');
        $this->assertCount(3, $stream->events());
    }

    /**
     * @test
     */
    public function saveAnAggregate()
    {
        $eventStore = new InMemoryEventStore([
            'id' => new EventStream($this->buildDummyDomainEvents(2))
        ]);
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $expectedVersion = 2;
        $aggregate = $this->buildAggregateMock('id', $changes, $expectedVersion);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream('id');
        $this->assertCount(5, $stream->events());
    }

    /**
     * @test
     */
    public function saveAnotherAggregate()
    {
        $eventStore = new InMemoryEventStore([
            'anotherId' => new EventStream($this->buildDummyDomainEvents(2))
        ]);
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $expectedVersion = 2;
        $aggregate = $this->buildAggregateMock('anotherId', $changes, $expectedVersion);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream('anotherId');
        $this->assertCount(5, $stream->events());
    }

    /**
     * @test
     */
    public function findAnAggregateCooperatesWithAggregateReconstructor()
    {
        $eventStore = new InMemoryEventStore([
            'DummyEventSourcedAggregate-id' => new EventStream([
                new DummyCreated('id', 'name', 'description'),
                new NameChanged('new name')
            ])
        ]);
        $aggregateReconstructor = $this->getMockBuilder(AggregateReconstructor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reconstitute'])
            ->getMock();
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'description'));
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $aggregateReconstructor
        );

        $aggregate = $repository->findById('id');

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     */
    public function afterAddAnAggregateItShouldNotContainChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $eventStore = $this->createMock(EventStore::class);
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $this->createMock(AggregateReconstructor::class)
        );

        $repository->add($aggregate);

        $this->assertCount(0, $aggregate->changes());
    }

    /**
     * @test
     */
    public function afterSaveAnAggregateItShouldNotContainChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $eventStore = $this->createMock(EventStore::class);
        $repository = new EventSourcedAggregateRepository(
            $eventStore,
            $this->createMock(AggregateReconstructor::class)
        );

        $repository->save($aggregate);

        $this->assertCount(0, $aggregate->changes());
    }

    /**
     * @param string $id
     * @param DomainEvent[] $changes
     * @param int $originalVersion
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function buildAggregateMock(
        $id,
        $changes = null,
        $originalVersion = null
    ) {
        $methods = ['id'];
        if (isset($changes)) {
            $methods[] = 'changes';
        }
        if (isset($originalVersion)) {
            $methods[] = 'originalVersion';
        }
        $aggregate = $this->getMockBuilder(EventSourcedAggregate::class)
            ->setMethods($methods)
            ->getMockForTrait();
        $aggregate
            ->method('id')
            ->willReturn($id);
        if (isset($changes)) {
            $aggregate
                ->expects($this->once())
                ->method('changes')
                ->willReturn($changes);
        }
        if (isset($originalVersion)) {
            $aggregate
                ->expects($this->once())
                ->method('originalVersion')
                ->willReturn($originalVersion);
        }
        return $aggregate;
    }

    /**
     * @param int $eventCount
     * @return DomainEvent[]
     */
    private function buildDummyDomainEvents($eventCount)
    {
        $event = $this->createMock(DomainEvent::class);
        $events = [];
        while ($eventCount > 0) {
            $events[] = $event;
            $eventCount--;
        }
        return $events;
    }
}
