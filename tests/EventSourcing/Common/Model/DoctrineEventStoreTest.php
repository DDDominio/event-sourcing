<?php

namespace Tests\EventSourcing\Common\Model;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use EventSourcing\Common\Model\DoctrineEventStore;
use EventSourcing\Common\Model\EventStream;
use EventSourcing\Common\Model\Snapshot;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Tests\EventSourcing\Common\Model\TestData\DescriptionChanged;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\DummySnapshot;
use Tests\EventSourcing\Common\Model\TestData\NameChanged;

class DoctrineEventStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    public function setUp()
    {
        $connectionParams = array(
            'dbname' => 'event_sourcing',
            'user' => 'event_sourcing',
            'password' => 'event_sourcing123',
            'host' => 'localhost',
            'driver' => 'pdo_mysql',
        );
        $config = new Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);

        $this->connection->query('TRUNCATE events')->execute();
        $this->connection->query('DELETE FROM streams')->execute();

        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation',
            __DIR__ . '/../../../../vendor/jms/serializer/src'
        );

        $this->serializer = SerializerBuilder::create()
            ->build();
    }

    /**
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $domainEvent = new NameChanged('name');

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
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $domainEvent = new NameChanged('name');

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
        $domainEvent = new NameChanged('name');
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->appendToStream('streamId', [$domainEvent]);

        $eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $domainEvent = new NameChanged('name');

        $eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $event = new NameChanged('name');
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->appendToStream('streamId', [$event]);

        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );

        $stream = $eventStore->readFullStream('NonExistentStreamId');

        $this->assertTrue($stream->isEmpty());
        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function findLastSnapshotOfAStream()
    {
        $snapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            3
        );
        $lastSnapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            10
        );
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->addSnapshot($snapshot);
        $eventStore->addSnapshot($lastSnapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot(
            DummyEventSourcedAggregate::class,
            'id'
        );

        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals(10, $retrievedSnapshot->version());
    }

    /**
     * @test
     */
    public function addAnSnapshot()
    {
        $snapshot = new DummySnapshot(
            'id',
            'name',
            'description',
            3
        );
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );

        $eventStore->addSnapshot($snapshot);

        $retrievedSnapshot = $eventStore->findLastSnapshot(
            DummyEventSourcedAggregate::class,
            'id'
        );
        $this->assertInstanceOf(Snapshot::class, $retrievedSnapshot);
        $this->assertEquals('id', $retrievedSnapshot->id());
        $this->assertEquals('name', $retrievedSnapshot->name());
        $this->assertEquals('description', $retrievedSnapshot->description());
        $this->assertEquals(3, $retrievedSnapshot->version());
    }

    /**
     * @test
     */
    public function findStreamEventsForward()
    {
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->appendToStream('streamId', [
            new NameChanged('new name'),
            new DescriptionChanged('new description'),
            new NameChanged('another name'),
            new NameChanged('my name'),
        ]);

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
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->appendToStream('streamId', [
            new NameChanged('new name'),
            new DescriptionChanged('new description'),
            new NameChanged('another name'),
            new NameChanged('my name'),
        ]);

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
        $eventStore = new DoctrineEventStore(
            $this->connection,
            $this->serializer
        );
        $eventStore->appendToStream('streamId', [
            new NameChanged('new name'),
            new DescriptionChanged('new description'),
            new NameChanged('another name'),
            new NameChanged('my name'),
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 5);

        $this->assertTrue($stream->isEmpty());
    }
}
