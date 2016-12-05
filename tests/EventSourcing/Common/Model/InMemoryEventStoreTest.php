<?php

namespace tests\EventSourcing\Common\Model;

use Doctrine\Common\Annotations\AnnotationRegistry;
use EventSourcing\Common\Model\DomainEvent;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\InMemoryEventStore;
use EventSourcing\Common\Model\Snapshot;
use EventSourcing\Common\Model\StoredEvent;
use EventSourcing\Common\Model\StoredEventStream;
use EventSourcing\Versioning\Version;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Tests\EventSourcing\Common\Model\TestData\DescriptionChanged;
use Tests\EventSourcing\Common\Model\TestData\DummyCreated;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\DummySnapshot;
use Tests\EventSourcing\Common\Model\TestData\NameChanged;

class InMemoryEventStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp()
    {
        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation', __DIR__ . '/../../../../vendor/jms/serializer/src'
        );
        $this->serializer = SerializerBuilder::create()
            ->build();
    }

    /**
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $eventStore = new InMemoryEventStore($this->serializer);
        $domainEvent = new NameChanged('name', new \DateTimeImmutable());

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
        $eventStore = new InMemoryEventStore($this->serializer);
        $domainEvent = new NameChanged('name', new \DateTimeImmutable());

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
        $storedEvent = $this->createMock(StoredEvent::class);
        $streamId = 'streamId';
        $storedEventStream = new StoredEventStream($streamId, [$storedEvent]);
        // expected version form streamId: 1
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            [$streamId => $storedEventStream]
        );
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $eventStore = new InMemoryEventStore($this->serializer);
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $domainEvent = new NameChanged('name', new \DateTimeImmutable());
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            get_class($domainEvent),
            $this->serializer->serialize($domainEvent, 'json'),
            $domainEvent->occurredOn(),
            Version::fromString('1.0')
        );
        $storedEventStream = new StoredEventStream('streamId', [$storedEvent]);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            ['streamId' => $storedEventStream]
        );

        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
        $this->assertInstanceOf(DomainEvent::class, $stream->events()[0]);
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $eventStore = new InMemoryEventStore($this->serializer);

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
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            [],
            [
                'aggregateClass' => ['aggregateId' => [$snapshot, $lastSnapshot]]
            ]
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
        $eventStore = new InMemoryEventStore($this->serializer);

        $eventStore->addSnapshot($snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot('aggregateClass', 'aggregateId');
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
    }

    /**
     * @test
     */
    public function findStreamEventsForward()
    {
        $domainEvents = [
            new NameChanged('new name', new \DateTimeImmutable()),
            new DescriptionChanged('new description', new \DateTimeImmutable()),
            new NameChanged('another name', new \DateTimeImmutable()),
            new NameChanged('my name', new \DateTimeImmutable()),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new StoredEventStream('streamId', $storedEvents);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            ['streamId' => $storedEventStream]
        );

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
        $domainEvents = [
            new NameChanged('new name', new \DateTimeImmutable()),
            new DescriptionChanged('new description', new \DateTimeImmutable()),
            new NameChanged('another name', new \DateTimeImmutable()),
            new NameChanged('my name', new \DateTimeImmutable()),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new StoredEventStream('streamId', $storedEvents);

        $eventStore = new InMemoryEventStore(
            $this->serializer,
            ['streamId' => $storedEventStream]
        );

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
        $domainEvents = [
            new NameChanged('new name', new \DateTimeImmutable()),
            new DescriptionChanged('new description', new \DateTimeImmutable()),
            new NameChanged('another name', new \DateTimeImmutable()),
            new NameChanged('my name', new \DateTimeImmutable()),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new StoredEventStream('streamId', $storedEvents);

        $eventStore = new InMemoryEventStore(
            $this->serializer,
            ['streamId' => $storedEventStream]
        );

        $stream = $eventStore->readStreamEventsForward('streamId', 5);

        $this->assertTrue($stream->isEmpty());
    }

    /**
     * @test
     */
    public function findSnapshotForEventVersion()
    {
        $domainEvents = [
            new DummyCreated('id', 'name', 'description', new \DateTimeImmutable()),
            new NameChanged('new name', new \DateTimeImmutable()),
            new DescriptionChanged('new description', new \DateTimeImmutable()),
            new NameChanged('another name', new \DateTimeImmutable()),
            new NameChanged('my name', new \DateTimeImmutable()),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new StoredEventStream('streamId', $storedEvents);
        $streams = ['streamId' => $storedEventStream];
        $snapshots = [
            DummyEventSourcedAggregate::class => ['id' => [
                new DummySnapshot('id', 'new name', 'description', 2),
                new DummySnapshot('id', 'another name', 'new description', 4),
            ]]
        ];
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $streams,
            $snapshots
        );

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 3);

        $this->assertEquals(2, $snapshot->version());
    }

    /**
     * @test
     */
    public function findSnapshotForAnotherEventVersion()
    {
        $domainEvents = [
            new DummyCreated('id', 'name', 'description', new \DateTimeImmutable()),
            new NameChanged('new name', new \DateTimeImmutable()),
            new DescriptionChanged('new description', new \DateTimeImmutable()),
            new NameChanged('another name', new \DateTimeImmutable()),
            new NameChanged('my name', new \DateTimeImmutable()),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new StoredEventStream('streamId', $storedEvents);
        $streams = ['streamId' => $storedEventStream];
        $snapshots = [
            DummyEventSourcedAggregate::class => ['id' => [
                new DummySnapshot('id', 'new name', 'description', 2),
                new DummySnapshot('id', 'another name', 'new description', 4),
            ]]
        ];
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $streams,
            $snapshots
        );

        $snapshot = $eventStore->findNearestSnapshotToVersion(DummyEventSourcedAggregate::class, 'id', 5);

        $this->assertEquals(4, $snapshot->version());
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
                $this->serializer->serialize($domainEvent, 'json'),
                $domainEvent->occurredOn(),
                Version::fromString('1.0')
            );
        }, $domainEvents);
    }
}
