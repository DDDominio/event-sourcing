<?php

namespace DDDominio\Tests\EventSourcing\Common;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRepositoryFactory;
use DDDominio\EventSourcing\Common\MethodAggregateIdExtractor;
use DDDominio\EventSourcing\EventStore\InMemoryEventStore;
use DDDominio\EventSourcing\Versioning\EventUpgraderInterface;
use DDDominio\EventSourcing\Common\AggregateReconstructor;
use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Snapshotting\InMemorySnapshotStore;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\Tests\EventSourcing\Serialization\DummySerializer;
use DDDominio\Tests\EventSourcing\TestData\DescriptionChanged;
use DDDominio\Tests\EventSourcing\TestData\DummyCreated;
use DDDominio\Tests\EventSourcing\TestData\DummyEventSourcedAggregate;
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

    /**
     * @var InMemoryEventStore
     */
    private $eventStore;

    /**
     * @var InMemorySnapshotStore
     */
    private $snapshotStore;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $aggregateRecontructor;

    /**
     * @var MethodAggregateIdExtractor
     */
    private $aggregateIdExtractor;

    /**
     * @var EventSourcedAggregateRepositoryFactory
     */
    private $repositoryFactory;

    protected function setUp()
    {
        $this->serializer = new DummySerializer();
        $this->eventUpgrader = $this->createMock(EventUpgraderInterface::class);
        $this->eventStore = $this->buildEmptyEventStore();
        $this->snapshotStore = new InMemorySnapshotStore();
        $this->aggregateRecontructor = $this->createMock(AggregateReconstructor::class);
        $this->aggregateIdExtractor = new MethodAggregateIdExtractor('id');
        $this->repositoryFactory = new EventSourcedAggregateRepositoryFactory(
            $this->eventStore,
            $this->snapshotStore,
            $this->aggregateRecontructor,
            $this->aggregateIdExtractor
        );
    }

    /**
     * @test
     */
    public function saveNewAggregates()
    {
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);
        $aggregateA = $this->buildAggregateMock('id-A', $this->buildDummyDomainEvents(3));
        $aggregateB = $this->buildAggregateMock('id-B', $this->buildDummyDomainEvents(1));

        $repository->save($aggregateA);
        $repository->save($aggregateB);

        $streamA = $this->eventStore->readFullStream(DummyEventSourcedAggregate::class . '-id-A');
        $streamB = $this->eventStore->readFullStream(DummyEventSourcedAggregate::class . '-id-B');
        $this->assertCount(3, $streamA);
        $this->assertCount(1, $streamB);
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function saveExistingAggregates()
    {
        $streamA = $this->buildDummyDomainEvents(2);
        $streamB = $this->buildDummyDomainEvents(1);
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class .'-id-A', $streamA);
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class .'-id-B', $streamB);
        $newChangesA = $this->buildDummyDomainEvents(3);
        $newChangesB = $this->buildDummyDomainEvents(7);
        $aggregateA = $this->buildAggregateMock('id-A', $newChangesA, 2);
        $aggregateB = $this->buildAggregateMock('id-B', $newChangesB, 1);
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        $repository->save($aggregateA);
        $repository->save($aggregateB);

        $streamA = $this->eventStore->readFullStream(DummyEventSourcedAggregate::class .'-id-A');
        $streamB = $this->eventStore->readFullStream(DummyEventSourcedAggregate::class .'-id-B');
        $this->assertCount(5, $streamA);
        $this->assertCount(8, $streamB);
    }

    /**
     * @test
     */
    public function afterSaveAnAggregateItShouldNotContainChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        $repository->save($aggregate);

        $this->assertCount(0, $aggregate->changes());
    }

    /**
     * @test
     * @dataProvider invalidAggregateProvider
     * @expectedException \InvalidArgumentException
     */
    public function ifTheAggregateTypeBeingSavedIsNoEqualToRepositoryTypeAnExceptionIsThrown($aggregate)
    {
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        $repository->save($aggregate);
    }

    /**
     * @return array
     */
    public function invalidAggregateProvider()
    {
        return [
            [new \stdClass()],
            [null]
        ];
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findAnAggregate()
    {
        $domainEvents = [
            DomainEvent::produceNow(new DummyCreated('id', 'name', 'description')),
            DomainEvent::produceNow(new NameChanged('new name'))
        ];
        $this->eventStore->appendToStream('DummyEventSourcedAggregate-id', $domainEvents);
        $this->aggregateRecontructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'description'));
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        /** @var DummyEventSourcedAggregate $aggregate */
        $aggregate = $repository->findById('id');

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findAnAggregateUsingLastSnapshot()
    {
        $streamUntilSnapshot = $this->buildDummyDomainEvents(10);
        $snapshot = new DummySnapshot('id', 'name', 'description', 10);
        $streamAfterSnapshot = [
            DomainEvent::produceNow(new NameChanged('new name'), [], new Version(1, 0)),
            DomainEvent::produceNow(new NameChanged('another name'), [], new Version(1, 0)),
        ];
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class . '-id', $streamUntilSnapshot);
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class . '-id', $streamAfterSnapshot, 10);
        $this->snapshotStore->addSnapshot($snapshot);
        $this->aggregateRecontructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with(DummyEventSourcedAggregate::class, new EventStream($streamAfterSnapshot), $snapshot);
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        $repository->findById('id');
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
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
        $this->eventStore->appendToStream('DummyEventSourcedAggregate-id', $domainEvents);
        $this->aggregateRecontructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'new description'));
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        /** @var DummyEventSourcedAggregate $aggregate */
        $aggregate = $repository->findByIdAndVersion('id', 3);

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('new description', $aggregate->description());
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findAnAggregateByIdAndVersionUsingTheClosestSnapshotToThatVersion()
    {
        $streamUntilSnapshot = [
            DomainEvent::produceNow(new DummyCreated('id', 'name', 'description')),
            DomainEvent::produceNow(new NameChanged('another name'))
        ];
        $snapshot = new DummySnapshot('id', 'another name', 'description', 2);
        $streamAfterSnapshot = [
            DomainEvent::produceNow(new DescriptionChanged('another description'), [], new Version(1, 0)),
            DomainEvent::produceNow(new NameChanged('new name'), [], new Version(1, 0))
        ];
        $this->snapshotStore->addSnapshot($snapshot);
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class . '-id', $streamUntilSnapshot);
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class . '-id', $streamAfterSnapshot, 2);
        $this->aggregateRecontructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with(DummyEventSourcedAggregate::class, new EventStream($streamAfterSnapshot), $snapshot);
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        $repository->findByIdAndVersion('id', 4);
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
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
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class.'-id', $domainEvents);
        $this->aggregateRecontructor
            ->expects($this->once())
            ->method('reconstitute')
            ->willReturn(new DummyEventSourcedAggregate('id', 'new name', 'description'));
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        /** @var DummyEventSourcedAggregate $aggregate */
        $aggregate = $repository->findByIdAndDatetime('id', new \DateTimeImmutable('2017-02-16 11:00:00'));

        $this->assertEquals('id', $aggregate->id());
        $this->assertEquals('new name', $aggregate->name());
        $this->assertEquals('description', $aggregate->description());
    }

    /**
     * @test
     * @throws \DDDominio\EventSourcing\EventStore\ConcurrencyException
     * @throws \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findAggregateByIdAndDatetimeUsingTheClosestSnapshotToThatDatetime()
    {
        $streamUntilSnapshot = [
            new DomainEvent(new DummyCreated('id', 'name', 'description'), [], new \DateTimeImmutable('2017-02-14 18:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-14 18:30:00'))
        ];
        $snapshot = new DummySnapshot('id', 'new name', 'description', 2);
        $streamAfterSnapshot = [
            new DomainEvent(new DummyCreated('id', 'name', 'description'), [], new \DateTimeImmutable('2017-02-15 12:00:00'), new Version(1, 0)),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00'), new Version(1, 0))
        ];
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class . '-id', $streamUntilSnapshot);
        $this->eventStore->appendToStream(DummyEventSourcedAggregate::class . '-id', $streamAfterSnapshot, 2);
        $this->snapshotStore->addSnapshot($snapshot);
        $this->aggregateRecontructor
            ->expects($this->once())
            ->method('reconstitute')
            ->with(DummyEventSourcedAggregate::class, new EventStream($streamAfterSnapshot), $snapshot);
        $repository = $this->repositoryFactory->build(DummyEventSourcedAggregate::class);

        $repository->findByIdAndDatetime('id', new \DateTimeImmutable('2017-02-16 11:00:00'));
    }

    /**
     * @return InMemoryEventStore
     */
    private function buildEmptyEventStore()
    {
        return new InMemoryEventStore($this->serializer, $this->eventUpgrader);
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
        $aggregate = $this->getMockBuilder(DummyEventSourcedAggregate::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
        $aggregate
            ->method('id')
            ->willReturn($id);
        if (isset($changes)) {
            $aggregate
                ->expects($this->any())
                ->method('changes')
                ->willReturn(new EventStream($changes));
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
        $event = DomainEvent::produceNow(new NameChanged('name'));
        $events = [];
        while ($eventCount > 0) {
            $events[] = $event;
            $eventCount--;
        }
        return $events;
    }
}
