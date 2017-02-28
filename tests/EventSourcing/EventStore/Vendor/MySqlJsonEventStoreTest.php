<?php

namespace DDDominio\Tests\EventSourcing\EventStore\Vendor;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\EventStore\Vendor\MySqlJsonEventStore;
use DDDominio\EventSourcing\Versioning\Version;
use Doctrine\Common\Annotations\AnnotationRegistry;
use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Versioning\EventAdapter;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use DDDominio\EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use JMS\Serializer\SerializerBuilder;
use DDDominio\Tests\EventSourcing\TestData\DescriptionChanged;
use DDDominio\Tests\EventSourcing\TestData\NameChanged;
use DDDominio\Tests\EventSourcing\TestData\VersionedEvent;
use DDDominio\Tests\EventSourcing\TestData\VersionedEventUpgrade10_20;

class MySqlJsonEventStoreTest extends \PHPUnit_Framework_TestCase
{
    const MYSQL_DB_HOST = 'localhost';
    const MYSQL_DB_USER = 'event_sourcing';
    const MYSQL_DB_PASS = 'event_sourcing123';
    const MYSQL_DB_NAME = 'json_event_store';

    /**
     * @var MySqlJsonEventStore
     */
    private $eventStore;

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    public function setUp()
    {
        $this->connection = new \PDO(
            'mysql:host='.self::MYSQL_DB_HOST,
            self::MYSQL_DB_USER,
            self::MYSQL_DB_PASS
        );

        AnnotationRegistry::registerLoader('class_exists');

        $this->serializer = new JsonSerializer(
            SerializerBuilder::create()
                ->addMetadataDir(
                    __DIR__ . '/../../TestData/Serializer',
                    'DDDominio\Tests\EventSourcing\TestData'
                )
                ->addMetadataDir(
                    __DIR__ . '/../../../../src/EventSourcing/Serialization/JmsMapping',
                    'DDDominio\EventSourcing\Common'
                )
                ->build()
        );

        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $this->eventUpgrader = new EventUpgrader($eventAdapter);
        $this->eventUpgrader->registerUpgrade(
            new VersionedEventUpgrade10_20($eventAdapter)
        );

        $this->destroyEventStore();
        $this->initializeEventStore();
    }

    protected function tearDown()
    {
        $this->destroyEventStore();
    }

    private function initializeEventStore()
    {
        $this->connection->query('CREATE SCHEMA '.self::MYSQL_DB_NAME);
        $this->connection->query('USE '.self::MYSQL_DB_NAME);
        $this->eventStore = new MySqlJsonEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $this->eventStore->initialize();
    }

    private function destroyEventStore()
    {
        $this->connection->query('DROP SCHEMA IF EXISTS '.self::MYSQL_DB_NAME);
    }

    /**
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $domainEvent = DomainEvent::record(new NameChanged('name'));

        $this->eventStore->appendToStream('streamId', [$domainEvent]);
        $stream = $this->eventStore->readFullStream('streamId');

        $this->assertInstanceOf(EventStream::class, $stream);
        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function appendAnEventToAnExistentStream()
    {
        $domainEvent = DomainEvent::record(new NameChanged('name'));

        $this->eventStore->appendToStream('streamId', [$domainEvent]);
        $this->eventStore->appendToStream('streamId', [$domainEvent], 1);
        $stream = $this->eventStore->readFullStream('streamId');

        $this->assertCount(2, $stream);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\ConcurrencyException
     */
    public function ifTheExpectedVersionOfTheStreamDoesNotMatchWithRealVersionAConcurrencyExceptionShouldBeThrown()
    {
        $domainEvent = DomainEvent::record(new NameChanged('name'));
        $this->eventStore->appendToStream('streamId', [$domainEvent]);

        $this->eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $domainEvent = DomainEvent::record(new NameChanged('name'));

        $this->eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $domainEvent = DomainEvent::record(new NameChanged('name'));
        $this->eventStore->appendToStream('streamId', [$domainEvent]);

        $stream = $this->eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $stream = $this->eventStore->readFullStream('NonExistentStreamId');

        $this->assertTrue($stream->isEmpty());
        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function findStreamEventsForward()
    {
        $this->eventStore->appendToStream('streamId', [
            DomainEvent::record(new NameChanged('new name')),
            DomainEvent::record(new DescriptionChanged('new description')),
            DomainEvent::record(new NameChanged('another name')),
            DomainEvent::record(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readStreamEvents('streamId', 2);

        $this->assertCount(3, $stream);
        $events = $stream->events();
        $this->assertEquals('new description', $events[0]->data()->description());
        $this->assertEquals('another name', $events[1]->data()->name());
        $this->assertEquals('my name', $events[2]->data()->name());
    }

    /**
     * @test
     */
    public function findStreamEventsForwardWithEventCount()
    {
        $this->eventStore->appendToStream('streamId', [
            DomainEvent::record(new NameChanged('new name')),
            DomainEvent::record(new DescriptionChanged('new description')),
            DomainEvent::record(new NameChanged('another name')),
            DomainEvent::record(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readStreamEvents('streamId', 2, 2);

        $this->assertCount(2, $stream);
        $events = $stream->events();
        $this->assertEquals('new description', $events[0]->data()->description());
        $this->assertEquals('another name', $events[1]->data()->name());
    }

    /**
     * @test
     */
    public function findStreamEventsForwardShouldReturnEmptyStreamIfStartVersionIsGreaterThanStreamVersion()
    {
        $this->eventStore->appendToStream('streamId', [
            DomainEvent::record(new NameChanged('new name')),
            DomainEvent::record(new DescriptionChanged('new description')),
            DomainEvent::record(new NameChanged('another name')),
            DomainEvent::record(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readStreamEvents('streamId', 5);

        $this->assertTrue($stream->isEmpty());
    }

    /**
     * @test
     */
    public function whenReadingFullStreamItShouldUpgradeOldStoredEvents()
    {
        $streamId = 'streamId';
        $stmt = $this->connection
            ->prepare('INSERT INTO streams (id) VALUES (:streamId)');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $stmt = $this->connection->prepare(
            'INSERT INTO events (stream_id, type, event, metadata, occurred_on, version)
                 VALUES (:streamId, :type, :event, :metadata, :occurredOn, :version)'
        );
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':type', VersionedEvent::class);
        $stmt->bindValue(':event', '{"name":"Name","occurredOn":"2016-12-04 17:35:35"}');
        $stmt->bindValue(':metadata', '{}');
        $stmt->bindValue(':occurredOn', '2016-12-04 17:35:35');
        $stmt->bindValue(':version', '1.0');
        $stmt->execute();

        $stream = $this->eventStore->readFullStream('streamId');

        $domainEvent = $stream->events()[0];
        $this->assertEquals('Name', $domainEvent->data()->username());
    }

    /**
     * @test
     */
    public function whenReadingStreamEventsForwardItShouldUpgradeOldStoredEvents()
    {
        $streamId = 'streamId';
        $stmt = $this->connection
            ->prepare('INSERT INTO streams (id) VALUES (:streamId)');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $stmt = $this->connection->prepare(
            'INSERT INTO events (stream_id, type, event, metadata, occurred_on, version)
                 VALUES (:streamId, :type, :event, :metadata, :occurredOn, :version)'
        );
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':type', VersionedEvent::class);
        $stmt->bindValue(':event', '{"name":"Name","occurredOn":"2016-12-04 17:35:35"}');
        $stmt->bindValue(':metadata', '{}');
        $stmt->bindValue(':occurredOn', '2016-12-04 17:35:35');
        $stmt->bindValue(':version', '1.0');
        $stmt->execute();

        $stream = $this->eventStore->readStreamEvents('streamId');

        $domainEvent = $stream->events()[0];
        $this->assertEquals('Name', $domainEvent->data()->username());
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findStreamEventVersionAtDatetimeOfNonExistingStream()
    {
        $this->eventStore->getStreamVersionAt('streamId', new \DateTimeImmutable('2017-02-16 12:00:00'));
    }

    /**
     * @test
     */
    public function findStreamEventVersionAtDatetime()
    {
        $this->eventStore->appendToStream('streamId', [
            new DomainEvent(new NameChanged('name'), [], new \DateTimeImmutable('2017-02-15 12:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00')),
            new DomainEvent(new DescriptionChanged('new description'), [], new \DateTimeImmutable('2017-02-16 11:00:01')),
            new DomainEvent(new NameChanged('another name'), [], new \DateTimeImmutable('2017-02-16 23:00:00')),
            new DomainEvent(new DescriptionChanged('another name'), [], new \DateTimeImmutable('2017-02-17 11:00:00')),
        ]);

        $version = $this->eventStore->getStreamVersionAt('streamId', new \DateTimeImmutable('2017-02-16 12:00:00'));

        $this->assertEquals(3, $version);
    }

    /**
     * @test
     */
    public function findStreamEventVersionAtDatetimeThatMatchWithEventOccurredOnTime()
    {
        $this->eventStore->appendToStream('streamId', [
            new DomainEvent(new NameChanged('name'), [], new \DateTimeImmutable('2017-02-15 12:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00')),
            new DomainEvent(new DescriptionChanged('new description'), [], new \DateTimeImmutable('2017-02-16 11:00:01')),
            new DomainEvent(new NameChanged('another name'), [], new \DateTimeImmutable('2017-02-16 23:00:00')),
            new DomainEvent(new DescriptionChanged('another name'), [], new \DateTimeImmutable('2017-02-17 11:00:00')),
        ]);

        $version = $this->eventStore->getStreamVersionAt('streamId', new \DateTimeImmutable('2017-02-16 11:00:00'));

        $this->assertEquals(2, $version);
    }

    /**
     * @test
     */
    public function itShouldUpgradeEventsInEventStore()
    {
        $streamId = 'streamId';
        $stmt = $this->connection
            ->prepare('INSERT INTO streams (id) VALUES (:streamId)');
        $stmt->bindValue(':streamId', $streamId);
        $stmt->execute();
        $stmt = $this->connection->prepare(
            'INSERT INTO events (stream_id, type, event, metadata, occurred_on, version)
                 VALUES (:streamId, :type, :event, :metadata, :occurredOn, :version)'
        );
        $stmt->bindValue(':streamId', $streamId);
        $stmt->bindValue(':type', VersionedEvent::class);
        $stmt->bindValue(':event', '{"name":"Name","occurred_on":"2016-12-04 17:35:35"}');
        $stmt->bindValue(':metadata', '{}');
        $stmt->bindValue(':occurredOn', '2016-12-04 17:35:35');
        $stmt->bindValue(':version', Version::fromString('1.0'));
        $stmt->execute();

        $this->eventStore->migrate(
            VersionedEvent::class,
            Version::fromString('1.0'),
            Version::fromString('2.0')
        );

        $stream = $this->eventStore->readFullStream('streamId');
        $this->assertCount(1, $stream);
        $event = $stream->events()[0];
        $this->assertTrue(Version::fromString('2.0')->equalTo($event->version()));
        $this->assertEquals('Name', $event->data()->username());
        $this->assertEquals('2016-12-04 17:35:35', $event->occurredOn()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function initializedEventStore()
    {
        $this->assertTrue($this->eventStore->initialized());
    }

    /**
     * @test
     */
    public function notInitializedEventStore()
    {
        $this->connection->exec('DROP TABLE events');
        $this->connection->exec('DROP TABLE streams');

        $this->assertFalse($this->eventStore->initialized());
    }

    /**
     * @test
     */
    public function whenEventStoreThrowsAnExceptionItIsConsideredNotInitialized()
    {
        $connection = $this->createMock(\PDO::class);
        $connection->method('query')->willThrowException(new \Exception());
        $this->eventStore = new MySqlJsonEventStore(
            $connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $this->assertFalse($this->eventStore->initialized());
    }
}
