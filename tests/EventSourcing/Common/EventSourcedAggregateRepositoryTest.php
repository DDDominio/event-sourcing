<?php

namespace Tests\EventSourcing\Common;

use Doctrine\Common\Annotations\AnnotationRegistry;
use EventSourcing\Common\AggregateReconstructor;
use EventSourcing\Common\EventSourcedAggregateRoot;
use EventSourcing\Common\DomainEvent;
use EventSourcing\Common\EventStore;
use EventSourcing\Common\EventStream;
use EventSourcing\Common\InMemoryEventStore;
use EventSourcing\Common\StoredEvent;
use EventSourcing\Common\StoredEventStream;
use EventSourcing\Serialization\JsonSerializer;
use EventSourcing\Serialization\Serializer;
use EventSourcing\Snapshotting\InMemorySnapshotStore;
use EventSourcing\Snapshotting\SnapshotStore;
use EventSourcing\Versioning\EventAdapter;
use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use EventSourcing\Versioning\Version;
use JMS\Serializer\SerializerBuilder;
use Tests\EventSourcing\Common\TestData\DummyCreated;
use Tests\EventSourcing\Common\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\TestData\DummyEventSourcedAggregateRepository;
use Tests\EventSourcing\Common\TestData\DummySnapshot;
use Tests\EventSourcing\Common\TestData\NameChanged;

class EventSourcedAggregateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    protected function setUp()
    {
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', __DIR__ . '/../../../vendor/jms/serializer/src'
        );
        $this->serializer = new JsonSerializer(
            SerializerBuilder::create()->build()
        );
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $this->eventUpgrader = new EventUpgrader($eventAdapter);
    }

    /**
     * @test
     */
    public function addAnAggregate()
    {
        $eventStore = new InMemoryEventStore($this->serializer, $this->eventUpgrader);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $aggregate = $this->buildAggregateMock('id', $changes);

        $repository->add($aggregate);

        $stream = $eventStore->readFullStream('DummyEventSourcedAggregate-id');
        $this->assertCount(3, $stream->events());
    }

    /**
     * @test
     */
    public function addAnotherAggregate()
    {
        $eventStore = new InMemoryEventStore($this->serializer, $this->eventUpgrader);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $aggregate = $this->buildAggregateMock('anotherId', $changes);

        $repository->add($aggregate);

        $stream = $eventStore->readFullStream('DummyEventSourcedAggregate-anotherId');
        $this->assertCount(3, $stream->events());
    }

    /**
     * @test
     */
    public function saveAnAggregate()
    {
        $stream = $this->buildDummyStoredEventStream('DummyEventSourcedAggregate-id', 2);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            [$stream->id() => $stream]
        );
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $expectedVersion = 2;
        $aggregate = $this->buildAggregateMock('id', $changes, $expectedVersion);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream('DummyEventSourcedAggregate-id');
        $this->assertCount(5, $stream->events());
    }

    /**
     * @test
     */
    public function saveAnotherAggregate()
    {
        $stream = $this->buildDummyStoredEventStream('DummyEventSourcedAggregate-anotherId', 2);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            [$stream->id() => $stream]
        );
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class)
        );
        $changes = $this->buildDummyDomainEvents(3);
        $expectedVersion = 2;
        $aggregate = $this->buildAggregateMock('anotherId', $changes, $expectedVersion);

        $repository->save($aggregate);

        $stream = $eventStore->readFullStream('DummyEventSourcedAggregate-anotherId');
        $this->assertCount(5, $stream->events());
    }

    /**
     * @test
     */
    public function afterAddAnAggregateItShouldNotContainChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $eventStore = $this->createMock(EventStore::class);
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
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
        $snapshotStore = new InMemorySnapshotStore();
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $this->createMock(AggregateReconstructor::class)
        );

        $repository->save($aggregate);

        $this->assertCount(0, $aggregate->changes());
    }


    /**
     * @test
     */
    public function findAnAggregateCooperatesWithAggregateReconstructor()
    {
        $domainEvents = [
            new DummyCreated('id', 'name', 'description', new \DateTimeImmutable()),
            new NameChanged('new name', new \DateTimeImmutable())
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $stream = new StoredEventStream('DummyEventSourcedAggregate-id', $storedEvents);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            [$stream->id() => $stream]
        );
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
    public function findAnAggregateThatHasStoredSnapshotsShouldUseItsLastSnapshot()
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
        $eventStore = $this->createMock(EventStore::class);
        $eventStore
            ->expects($this->once())
            ->method('readStreamEventsForward')
            ->with('DummyEventSourcedAggregate-id', $snapshot->version() + 1)
            ->willReturn($stream);
        $snapshotStore = $this->createMock(SnapshotStore::class);
        $snapshotStore
            ->expects($this->once())
            ->method('findLastSnapshot')
            ->willReturn($snapshot);
        $aggregateReconstructor = $this->createMock(AggregateReconstructor::class);
        $aggregateReconstructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with('DummyEventSourcedAggregate', $stream, $snapshot);
        $repository = new DummyEventSourcedAggregateRepository(
            $eventStore,
            $snapshotStore,
            $aggregateReconstructor
        );

        $repository->findById('id');
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
        $event = new NameChanged('name', new \DateTimeImmutable());
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
                get_class($domainEvent),
                $this->serializer->serialize($domainEvent),
                $domainEvent->occurredOn(),
                Version::fromString('1.0')
            );
        }, $domainEvents);
    }
}
