<?php

namespace DDDominio\Tests\EventSourcing\Common;

use DDDominio\EventSourcing\Common\MethodAggregateIdExtractor;
use DDDominio\EventSourcing\EventStore\EventStoreInterface;
use DDDominio\EventSourcing\EventStore\InMemoryEventStore;
use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\EventStore\StoredEventStream;
use DDDominio\EventSourcing\Versioning\EventUpgraderInterface;
use DDDominio\EventSourcing\Common\AggregateReconstructor;
use DDDominio\EventSourcing\Common\EventSourcedAggregateRoot;
use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Snapshotting\InMemorySnapshotStore;
use DDDominio\EventSourcing\Snapshotting\SnapshotStoreInterface;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\Tests\EventSourcing\TestData\DescriptionChanged;
use DDDominio\Tests\EventSourcing\TestData\DummyCreated;
use DDDominio\Tests\EventSourcing\TestData\DummyEventSourcedAggregate;
use DDDominio\Tests\EventSourcing\TestData\DummyEventSourcedAggregateRepository;
use DDDominio\Tests\EventSourcing\TestData\DummySnapshot;
use DDDominio\Tests\EventSourcing\TestData\NameChanged;

class EventSourcedAggregateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    protected function setUp()
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->eventUpgrader = $this->createMock(EventUpgraderInterface::class);
    }

    /**
     * @test
     */
    public function addANewAggregate()
    {
        $eventStore = $this->buildEmptyEventStore();
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class),
            new MethodAggregateIdExtractor('id')
        );
        $changes = $this->buildDummyDomainEvents(3);
        $aggregate = $this->buildAggregateMock('id', $changes);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream(DummyEventSourcedAggregate::class . '-id');
        $this->assertCount(3, $stream);
    }

    /**
     * @test
     */
    public function addAnotherNewAggregate()
    {
        $eventStore = $this->buildEmptyEventStore();
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class),
            new MethodAggregateIdExtractor('id')
        );
        $changes = $this->buildDummyDomainEvents(3);
        $aggregate = $this->buildAggregateMock('anotherId', $changes);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream(DummyEventSourcedAggregate::class . '-anotherId');
        $this->assertCount(3, $stream);
    }

    /**
     * @test
     */
    public function saveAnAggregate()
    {
        $stream = $this->buildDummyStoredEventStream(DummyEventSourcedAggregate::class .'-id', 2);
        $eventStore = $this->buildEventStoreWithStream($stream);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class),
            new MethodAggregateIdExtractor('id')
        );
        $changes = $this->buildDummyDomainEvents(3);
        $expectedVersion = 2;
        $aggregate = $this->buildAggregateMock('id', $changes, $expectedVersion);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream(DummyEventSourcedAggregate::class .'-id');
        $this->assertCount(5, $stream);
    }

    /**
     * @test
     */
    public function saveAnotherAggregate()
    {
        $stream = $this->buildDummyStoredEventStream(DummyEventSourcedAggregate::class .'-anotherId', 2);
        $eventStore = $this->buildEventStoreWithStream($stream);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class),
            new MethodAggregateIdExtractor('id')
        );
        $changes = $this->buildDummyDomainEvents(3);
        $expectedVersion = 2;
        $aggregate = $this->buildAggregateMock('anotherId', $changes, $expectedVersion);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream(DummyEventSourcedAggregate::class .'-anotherId');
        $this->assertCount(5, $stream);
    }

    /**
     * @test
     */
    public function afterAddANewAggregateItShouldNotContainChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $eventStore = $this->createMock(EventStoreInterface::class);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class),
            new MethodAggregateIdExtractor('id')
        );

        $repository->save($aggregate);

        $this->assertCount(0, $aggregate->changes());
    }

    /**
     * @test
     */
    public function afterSaveAnAggregateItShouldNotContainChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $eventStore = $this->createMock(EventStoreInterface::class);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class),
            new MethodAggregateIdExtractor('id')
        );

        $repository->save($aggregate);

        $this->assertCount(0, $aggregate->changes());
    }


    /**
     * @test
     */
    public function findAnAggregate()
    {
        $domainEvents = [
            DomainEvent::produceNow(
                new DummyCreated('id', 'name', 'description')
            ),
            DomainEvent::produceNow(
                new NameChanged('new name')
            )
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $stream = new StoredEventStream('DummyEventSourcedAggregate-id', $storedEvents);
        $eventStore = $this->buildEventStoreWithStream($stream);
        $aggregateReconstructor = $this->getMockBuilder(AggregateReconstructor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reconstitute'])
            ->getMock();
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'description'));
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            new MethodAggregateIdExtractor('id')
        );

        $aggregate = $repository->findById('id');

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     */
    public function findAnAggregateUsingLastSnapshot()
    {
        $snapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            10
        );
        $stream = new EventStream([
            new NameChanged('new name', new \DateTimeImmutable()),
            new NameChanged('another name', new \DateTimeImmutable()),
        ]);
        $eventStore = $this->createMock(EventStoreInterface::class);
        $eventStore
            ->expects($this->once())
            ->method('readStreamEvents')
            ->with(DummyEventSourcedAggregate::class . '-id', $snapshot->version() + 1)
            ->willReturn($stream);
        $snapshotStore = $this->createMock(SnapshotStoreInterface::class);
        $snapshotStore
            ->expects($this->once())
            ->method('findLastSnapshot')
            ->willReturn($snapshot);
        $aggregateReconstructor = $this->createMock(AggregateReconstructor::class);
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with(DummyEventSourcedAggregate::class, $stream, $snapshot);
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            new MethodAggregateIdExtractor('id')
        );

        $repository->findById('id');
    }

    /**
     * @test
     */
    public function findAnAggregateByIdAndVersion()
    {
        $domainEvents = [
            DomainEvent::produceNow(new DummyCreated('id', 'name', 'description')),
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new DescriptionChanged('another name')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $stream = new StoredEventStream('DummyEventSourcedAggregate-id', $storedEvents);
        $eventStore = $this->buildEventStoreWithStream($stream);
        $aggregateReconstructor = $this->getMockBuilder(AggregateReconstructor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reconstitute'])
            ->getMock();
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'new description'));
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            new MethodAggregateIdExtractor('id')
        );

        $aggregate = $repository->findByIdAndVersion('id', 3);

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('new description', $aggregate->description());
    }

    /**
     * @test
     */
    public function findAnAggregateByIdAndVersionUsingTheClosestSnapshotToThatVersion()
    {
        $snapshot = new DummySnapshot(
            'id',
            'new name',
            'description',
            2
        );
        $stream = [
            DomainEvent::produceNow(new DescriptionChanged('new description')),
        ];
        $eventStore = $this->createMock(EventStoreInterface::class);
        $eventStore
            ->expects($this->once())
            ->method('readStreamEvents')
            ->with(DummyEventSourcedAggregate::class . '-id', $snapshot->version() + 1)
            ->willReturn($stream);
        $snapshotStore = $this->createMock(SnapshotStoreInterface::class);
        $snapshotStore
            ->expects($this->once())
            ->method('findNearestSnapshotToVersion')
            ->willReturn($snapshot);
        $aggregateReconstructor = $this->createMock(AggregateReconstructor::class);
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with(DummyEventSourcedAggregate::class, $stream, $snapshot);
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            new MethodAggregateIdExtractor('id')
        );

        $repository->findByIdAndVersion('id', 4);
    }

    /**
     * @test
     */
    public function findAggregateByIdAndDatetime()
    {
        $domainEventsUntil = [
            new DomainEvent(new DummyCreated('id', 'name', 'description'), [], new \DateTimeImmutable('2017-02-15 12:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00'))
        ];
        $domainEvents = array_merge($domainEventsUntil, [
            new DomainEvent(new DescriptionChanged('new description'), [], new \DateTimeImmutable('2017-02-16 11:00:01')),
            new DomainEvent(new NameChanged('another name'), [], new \DateTimeImmutable('2017-02-16 23:00:00')),
            new DomainEvent(new DescriptionChanged('another name'), [], new \DateTimeImmutable('2017-02-17 11:00:00')),
        ]);
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $stream = new StoredEventStream(DummyEventSourcedAggregate::class.'-id', $storedEvents);
        $eventStore = $this->buildEventStoreWithStream($stream);
        $aggregateReconstructor = $this->getMockBuilder(AggregateReconstructor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reconstitute'])
            ->getMock();
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'description'));
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            new MethodAggregateIdExtractor('id')
        );

        $aggregate = $repository->findByIdAndDatetime('id', new \DateTimeImmutable('2017-02-16 11:00:00'));

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     */
    public function findAggregateByIdAndDatetimeUsingTheClosestSnapshotToThatDatetime()
    {
        $snapshot = new DummySnapshot(
            'id',
            'new name',
            'description',
            2
        );
        $stream = [
            new DomainEvent(new DummyCreated('id', 'name', 'description'), [], new \DateTimeImmutable('2017-02-15 12:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00'))
        ];
        $datetime = new \DateTimeImmutable('2017-02-16 11:00:00');
        $eventStore = $this->createMock(EventStoreInterface::class);
        $eventStore
            ->expects($this->once())
            ->method('getStreamVersionAt')
            ->willReturn(4);
        $eventStore
            ->expects($this->once())
            ->method('readStreamEvents')
            ->with(DummyEventSourcedAggregate::class . '-id', 3, 2)
            ->willReturn($stream);
        $snapshotStore = $this->createMock(SnapshotStoreInterface::class);
        $snapshotStore
            ->expects($this->once())
            ->method('findNearestSnapshotToVersion')
            ->willReturn($snapshot);
        $aggregateReconstructor = $this->createMock(AggregateReconstructor::class);
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with(DummyEventSourcedAggregate::class, $stream, $snapshot);
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor,
            new MethodAggregateIdExtractor('id')
        );

        $repository->findByIdAndDatetime('id', $datetime);
    }

    /**
     * @return InMemoryEventStore
     */
    private function buildEmptyEventStore()
    {
        return new InMemoryEventStore($this->serializer, $this->eventUpgrader);
    }

    /**
     * @param EventStream $stream
     * @return InMemoryEventStore
     */
    private function buildEventStoreWithStream($stream)
    {
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            [$stream->id() => $stream]
        );
        return $eventStore;
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
        $aggregate = $this->getMockBuilder(EventSourcedAggregateRoot::class)
            ->setMethods($methods)
            ->getMock();
        $aggregate
            ->method('id')
            ->willReturn($id);
        if (isset($changes)) {
            $aggregate
                ->expects($this->any())
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
     * @param string $id
     * @param int $eventCount
     * @return StoredEventStream
     */
    private function buildDummyStoredEventStream($id, $eventCount)
    {
        $domainEvents = $this->buildDummyDomainEvents($eventCount);
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        return new StoredEventStream($id, $storedEvents);
    }

    /**
     * @param int $eventCount
     * @return DomainEvent[]
     */
    private function buildDummyDomainEvents($eventCount)
    {
        $event = DomainEvent::produceNow(new NameChanged('name'));
        $events = [];
        while ($eventCount > 0) {
            $events[] = $event;
            $eventCount--;
        }
        return $events;
    }

    /**
     * @param DomainEvent[] $domainEvents
     * @return StoredEvent[]
     */
    private function storedEventsFromDomainEvents($domainEvents)
    {
        return array_map(function(DomainEvent $domainEvent) {
            return new StoredEvent(
                'id',
                'streamId',
                get_class($domainEvent->data()),
                $this->serializer->serialize($domainEvent->data()),
                json_encode($domainEvent->metadata()),
                $domainEvent->occurredOn(),
                Version::fromString('1.0')
            );
        }, $domainEvents);
    }
}
