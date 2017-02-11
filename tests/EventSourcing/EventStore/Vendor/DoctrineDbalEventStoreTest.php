<?php

namespace DDDominio\Tests\EventSourcing\EventStore\Vendor;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\EventStore\Vendor\DoctrineDbalEventStore;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\EventSourcing\Versioning\EventAdapter;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use DDDominio\EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use DDDominio\EventSourcing\Versioning\Version;
use JMS\Serializer\SerializerBuilder;
use DDDominio\Tests\EventSourcing\TestData\DescriptionChanged;
use DDDominio\Tests\EventSourcing\TestData\NameChanged;
use DDDominio\Tests\EventSourcing\TestData\VersionedEvent;
use DDDominio\Tests\EventSourcing\TestData\VersionedEventUpgrade10_20;

class DoctrineDbalEventStoreTest extends \PHPUnit_Framework_TestCase
{
    const TEST_DB_PATH = __DIR__ . '/../test.db';

    /**
     * @var Connection
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

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->destroyTestDatabaseIfExists();
        $this->createTestDatabase();

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
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->destroyTestDatabaseIfExists();
    }

    private function createTestDatabase()
    {
        touch(self::TEST_DB_PATH);
        $connectionParams = array(
            'path' => self::TEST_DB_PATH,
            'host' => 'localhost',
            'driver' => 'pdo_sqlite',
        );
        $config = new Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);
        $this->connection->exec(
            file_get_contents(__DIR__ . '/../../TestData/doctrine_dbal_event_store_schema.sql')
        );
    }

    private function destroyTestDatabaseIfExists()
    {
        if (file_exists(self::TEST_DB_PATH)) {
            unlink(self::TEST_DB_PATH);
        }
    }

    /**
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $domainEvent = DomainEvent::record(
            new NameChanged('name')
        );

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
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $domainEvent = DomainEvent::record(
            new NameChanged('name')
        );

        $eventStore->appendToStream('streamId', [$domainEvent]);
        $eventStore->appendToStream('streamId', [$domainEvent], 1);
        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(2, $stream);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\ConcurrencyException
     */
    public function ifTheExpectedVersionOfTheStreamDoesNotMatchWithRealVersionAConcurrencyExceptionShouldBeThrown()
    {
        $domainEvent = DomainEvent::record(
            new NameChanged('name')
        );
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->appendToStream('streamId', [$domainEvent]);

        $eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $domainEvent = DomainEvent::record(new NameChanged('name'));

        $eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $event = DomainEvent::record(
            new NameChanged('name')
        );
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
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
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $stream = $eventStore->readFullStream('NonExistentStreamId');

        $this->assertTrue($stream->isEmpty());
        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function findStreamEventsForward()
    {
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->appendToStream('streamId', [
            DomainEvent::record(new NameChanged('new name')),
            DomainEvent::record(new DescriptionChanged('new description')),
            DomainEvent::record(new NameChanged('another name')),
            DomainEvent::record(new NameChanged('my name')),
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 2);

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
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->appendToStream('streamId', [
            DomainEvent::record(new NameChanged('new name')),
            DomainEvent::record(new DescriptionChanged('new description')),
            DomainEvent::record(new NameChanged('another name')),
            DomainEvent::record(new NameChanged('my name')),
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 2, 2);

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
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->appendToStream('streamId', [
            DomainEvent::record(new NameChanged('new name')),
            DomainEvent::record(new DescriptionChanged('new description')),
            DomainEvent::record(new NameChanged('another name')),
            DomainEvent::record(new NameChanged('my name')),
        ]);

        $stream = $eventStore->readStreamEventsForward('streamId', 5);

        $this->assertTrue($stream->isEmpty());
    }

    /**
     * @test
     */
    public function whenReadingAStreamItShouldUpgradeOldStoredEvents()
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
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $stream = $eventStore->readFullStream('streamId');

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
        $stmt->bindValue(':event', '{"name":"Name","occurred_on":"2016-12-04 17:35:35"}');
        $stmt->bindValue(':metadata', '{}');
        $stmt->bindValue(':occurredOn', '2016-12-04 17:35:35');
        $stmt->bindValue(':version', Version::fromString('1.0'));
        $stmt->execute();
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $stream = $eventStore->readStreamEventsForward('streamId');

        $domainEvent = $stream->events()[0];
        $this->assertEquals('Name', $domainEvent->data()->username());
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
        $eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $eventStore->migrate(
            VersionedEvent::class,
            Version::fromString('1.0'),
            Version::fromString('2.0')
        );

        $stream = $eventStore->readFullStream('streamId');
        $this->assertCount(1, $stream);
        $event = $stream->events()[0];
        $this->assertTrue(Version::fromString('2.0')->equalTo($event->version()));
        $this->assertEquals('Name', $event->data()->username());
        $this->assertEquals('2016-12-04 17:35:35', $event->occurredOn()->format('Y-m-d H:i:s'));
    }
}
