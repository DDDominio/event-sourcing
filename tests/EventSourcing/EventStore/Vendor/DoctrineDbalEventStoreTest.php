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
     * @var DoctrineDbalEventStore
     */
    private $eventStore;

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

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->destroyEventStore();
    }

    private function initializeEventStore()
    {
        touch(self::TEST_DB_PATH);
        $connectionParams = array(
            'path' => self::TEST_DB_PATH,
            'host' => 'localhost',
            'driver' => 'pdo_sqlite',
        );
        $config = new Configuration();
        $this->connection = DriverManager::getConnection($connectionParams, $config);
        $this->eventStore = new DoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $this->eventStore->initialize();
    }

    private function destroyEventStore()
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
        $domainEvent = DomainEvent::produceNow(
            new NameChanged('name')
        );

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
        $domainEvent = DomainEvent::produceNow(
            new NameChanged('name')
        );

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
        $domainEvent = DomainEvent::produceNow(
            new NameChanged('name')
        );
        $this->eventStore->appendToStream('streamId', [$domainEvent]);

        $this->eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $domainEvent = DomainEvent::produceNow(new NameChanged('name'));

        $this->eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\ConcurrencyException
     */
    public function afterAppendingEventsIfTheFinalVersionIsGreaterThanExpectedAConcurrencyExceptionMustBeThown()
    {
        $domainEvent = DomainEvent::produceNow(new NameChanged('name'));
        $this->eventStore = new ConcurrencyExceptionDoctrineDbalEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $this->eventStore->appendToStream('newStreamId', [$domainEvent]);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $event = DomainEvent::produceNow(
            new NameChanged('name')
        );
        $this->eventStore->appendToStream('streamId', [$event]);

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
    public function findStreamEvents()
    {
        $this->eventStore->appendToStream('streamId', [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readStreamEvents('streamId', 2);

        $this->assertCount(3, $stream);
        $this->assertEquals('new description', $stream->get(0)->data()->description());
        $this->assertEquals('another name', $stream->get(1)->data()->name());
        $this->assertEquals('my name', $stream->get(2)->data()->name());
    }

    /**
     * @test
     */
    public function findStreamEventsWithEventCount()
    {
        $this->eventStore->appendToStream('streamId', [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readStreamEvents('streamId', 2, 2);

        $this->assertCount(2, $stream);
        $this->assertEquals('new description', $stream->get(0)->data()->description());
        $this->assertEquals('another name', $stream->get(1)->data()->name());
    }

    /**
     * @test
     */
    public function findStreamEventsShouldReturnEmptyStreamIfStartVersionIsGreaterThanStreamVersion()
    {
        $this->eventStore->appendToStream('streamId', [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readStreamEvents('streamId', 5);

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

        $stream = $this->eventStore->readFullStream('streamId');

        $this->assertEquals('Name', $stream->get(0)->data()->username());
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

        $stream = $this->eventStore->readStreamEvents('streamId');

        $this->assertEquals('Name', $stream->get(0)->data()->username());
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
        $event = $stream->get(0);
        $this->assertTrue(Version::fromString('2.0')->equalTo($event->version()));
        $this->assertEquals('Name', $event->data()->username());
        $this->assertEquals('2016-12-04 17:35:35', $event->occurredOn()->format('Y-m-d H:i:s'));
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
    public function readAllEvents()
    {
        $this->eventStore->appendToStream('stream1', [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
        ]);
        $this->eventStore->appendToStream('stream2', [
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ]);

        $stream = $this->eventStore->readAllEvents();

        $this->assertCount(4, $stream);
        $this->assertEquals('new name', $stream->get(0)->data()->name());
        $this->assertEquals('new description', $stream->get(1)->data()->description());
        $this->assertEquals('another name', $stream->get(2)->data()->name());
        $this->assertEquals('my name', $stream->get(3)->data()->name());
    }

    /**
     * @test
     */
    public function readAllStreams()
    {
        $this->eventStore->appendToStream('stream1', [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
        ]);
        $this->eventStore->appendToStream('stream2', [
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ]);

        $streams = $this->eventStore->readAllStreams();

        $this->assertCount(2, $streams);
        $this->assertEquals('new name', $streams[0]->get(0)->data()->name());
        $this->assertEquals('new description', $streams[0]->get(1)->data()->description());
        $this->assertEquals('another name', $streams[1]->get(0)->data()->name());
        $this->assertEquals('my name', $streams[1]->get(1)->data()->name());
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
}

class ConcurrencyExceptionDoctrineDbalEventStore extends DoctrineDbalEventStore
{
    protected function streamVersion($streamId)
    {
        return 1000;
    }
}
