<?php

namespace DDDominio\Tests\EventSourcing\EventStore;

use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\EventStore\MySqlJsonEventStore;
use Doctrine\Common\Annotations\AnnotationRegistry;
use DDDominio\EventSourcing\Common\EventStream;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Serialization\Serializer;
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
    const DB_HOST = 'localhost';
    const DB_USER = 'event_sourcing';
    const DB_PASS = 'event_sourcing123';
    const DB_NAME = 'json_event_store';

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var EventUpgrader
     */
    private $eventUpgrader;

    public function setUp()
    {
        $this->connection = new \PDO(
            'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME,
            self::DB_USER,
            self::DB_PASS
        );
        $this->connection->query('TRUNCATE events')->execute();
        $this->connection->query('DELETE FROM streams')->execute();

        AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation',
            __DIR__ . '/../../../vendor/jms/serializer/src'
        );

        $this->serializer = new JsonSerializer(
            SerializerBuilder::create()
                ->addMetadataDir(
                    __DIR__ . '/../TestData/Serializer',
                    'DDDominio\Tests\EventSourcing\TestData'
                )
                ->addMetadataDir(
                    __DIR__ . '/../../../src/EventSourcing/Serialization/JmsMapping',
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
     * @test
     */
    public function appendAnEventToANewStreamShouldCreateAStreamContainingTheEvent()
    {
        $eventStore = new MySqlJsonEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $domainEvent = DomainEvent::record(new NameChanged('name'));

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
        $eventStore = new MySqlJsonEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $domainEvent = DomainEvent::record(new NameChanged('name'));

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
        $domainEvent = DomainEvent::record(new NameChanged('name'));
        $eventStore = new MySqlJsonEventStore(
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
        $eventStore = new MySqlJsonEventStore(
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
        $eventStore = new MySqlJsonEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );
        $domainEvent = DomainEvent::record(new NameChanged('name'));
        $eventStore->appendToStream('streamId', [$domainEvent]);

        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $eventStore = new MySqlJsonEventStore(
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
        $eventStore = new MySqlJsonEventStore(
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
        $eventStore = new MySqlJsonEventStore(
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
        $eventStore = new MySqlJsonEventStore(
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
        $eventStore = new MySqlJsonEventStore(
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
        $stmt->bindValue(':event', '{"name":"Name","occurredOn":"2016-12-04 17:35:35"}');
        $stmt->bindValue(':metadata', '{}');
        $stmt->bindValue(':occurredOn', '2016-12-04 17:35:35');
        $stmt->bindValue(':version', '1.0');
        $stmt->execute();
        $eventStore = new MySqlJsonEventStore(
            $this->connection,
            $this->serializer,
            $this->eventUpgrader
        );

        $stream = $eventStore->readStreamEventsForward('streamId');

        $domainEvent = $stream->events()[0];
        $this->assertEquals('Name', $domainEvent->data()->username());
    }
}