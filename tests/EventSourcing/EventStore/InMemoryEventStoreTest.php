<?php

namespace DDDominio\Tests\EventSourcing\EventStore;

use DDDominio\EventSourcing\EventStore\EventStoreEvents;
use DDDominio\EventSourcing\EventStore\EventStoreInterface;
use DDDominio\EventSourcing\EventStore\InMemoryEventStore;
use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\EventStore\IdentifiedEventStream;
use DDDominio\Tests\EventSourcing\TestData\RecorderEventListener;
use Doctrine\Common\Annotations\AnnotationRegistry;
use DDDominio\EventSourcing\Common\DomainEvent;
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

class InMemoryEventStoreTest extends \PHPUnit_Framework_TestCase
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
        AnnotationRegistry::registerLoader('class_exists');
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
        $eventStore = new InMemoryEventStore($this->serializer, $this->eventUpgrader);
        $domainEvent = DomainEvent::produceNow(new NameChanged('name'));

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
        $eventStore = new InMemoryEventStore($this->serializer, $this->eventUpgrader);
        $domainEvent = DomainEvent::produceNow(new NameChanged('name'));

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
        $storedEvent = $this->createMock(StoredEvent::class);
        $streamId = 'streamId';
        $storedEventStream = new IdentifiedEventStream($streamId, [$storedEvent]);
        // expected version form streamId: 1
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            [$streamId => $storedEventStream]
        );
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('streamId', [$domainEvent]);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function whenAppendingToANewStreamIfAVersionIsSpecifiedAnExceptionShouldBeThrown()
    {
        $eventStore = new InMemoryEventStore($this->serializer, $this->eventUpgrader);
        $domainEvent = $this->createMock(DomainEvent::class);

        $eventStore->appendToStream('newStreamId', [$domainEvent], 10);
    }

    /**
     * @test
     */
    public function readAnEventStream()
    {
        $domainEvent = DomainEvent::produceNow(new NameChanged('name'));
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            get_class($domainEvent->data()),
            $this->serializer->serialize($domainEvent->data()),
            $this->serializer->serialize($domainEvent->metadata()),
            $domainEvent->occurredOn(),
            Version::fromString('1.0')
        );
        $storedEventStream = new IdentifiedEventStream('streamId', [$storedEvent]);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $stream = $eventStore->readFullStream('streamId');

        $this->assertCount(1, $stream);
        $this->assertInstanceOf(NameChanged::class, $stream->get(0)->data());
    }

    /**
     * @test
     */
    public function readAnEmptyStream()
    {
        $eventStore = new InMemoryEventStore($this->serializer, $this->eventUpgrader);

        $stream = $eventStore->readFullStream('NonExistentStreamId');

        $this->assertTrue($stream->isEmpty());
        $this->assertCount(0, $stream);
    }

    /**
     * @test
     */
    public function findStreamEventsForward()
    {
        $domainEvents = [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new IdentifiedEventStream('streamId', $storedEvents);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $stream = $eventStore->readStreamEvents('streamId', 2);

        $this->assertCount(3, $stream);
        $this->assertEquals('new description', $stream->get(0)->data()->description());
        $this->assertEquals('another name', $stream->get(1)->data()->name());
        $this->assertEquals('my name', $stream->get(2)->data()->name());
    }

    /**
     * @test
     */
    public function findStreamEventsForwardWithEventCount()
    {
        $domainEvents = [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new IdentifiedEventStream('streamId', $storedEvents);

        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $stream = $eventStore->readStreamEvents('streamId', 2, 2);

        $this->assertCount(2, $stream);
        $this->assertEquals('new description', $stream->get(0)->data()->description());
        $this->assertEquals('another name', $stream->get(1)->data()->name());
    }

    /**
     * @test
     */
    public function findStreamEventsForwardShouldReturnEmptyStreamIfStartVersionIsGreaterThanStreamVersion()
    {
        $domainEvents = [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new IdentifiedEventStream('streamId', $storedEvents);

        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $stream = $eventStore->readStreamEvents('streamId', 5);

        $this->assertTrue($stream->isEmpty());
    }

    /**
     * @test
     */
    public function whenReadingFullStreamItShouldUpgradeOldStoredEvents()
    {
        $oldStoredEvent = new StoredEvent(
            'id',
            'streamId',
            VersionedEvent::class,
            '{"name":"Name","occurredOn":"2016-12-04 17:35:35"}',
            '{}',
            new \DateTimeImmutable('2016-12-04 17:35:35'),
            Version::fromString('1.0')
        );
        $storedEventStream = new IdentifiedEventStream('streamId', [$oldStoredEvent]);
        $streams = [$storedEventStream->id() => $storedEventStream];
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            $streams
        );

        $stream = $eventStore->readFullStream('streamId');

        $this->assertEquals('Name', $stream->get(0)->data()->username());
    }

    /**
     * @test
     */
    public function whenReadingStreamEventsForwardItShouldUpgradeOldStoredEvents()
    {
        $oldStoredEvent = new StoredEvent(
            'id',
            'streamId',
            VersionedEvent::class,
            '{"name":"Name","occurredOn":"2016-12-04 17:35:35"}',
            '{}',
            new \DateTimeImmutable('2016-12-04 17:35:35'),
            Version::fromString('1.0')
        );
        $storedEventStream = new IdentifiedEventStream('streamId', [$oldStoredEvent]);
        $streams = [$storedEventStream->id() => $storedEventStream];
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            $streams
        );

        $stream = $eventStore->readStreamEvents('streamId');

        $this->assertEquals('Name', $stream->get(0)->data()->username());
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\EventStore\EventStreamDoesNotExistException
     */
    public function findStreamEventVersionAtDatetimeOfNonExistingStream()
    {
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader
        );

        $eventStore->getStreamVersionAt('streamId', new \DateTimeImmutable('2017-02-16 12:00:00'));
    }

    /**
     * @test
     */
    public function findStreamEventVersionAtDatetime()
    {
        $domainEvents = [
            new DomainEvent(new NameChanged('name'), [], new \DateTimeImmutable('2017-02-15 12:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00')),
            new DomainEvent(new DescriptionChanged('new description'), [], new \DateTimeImmutable('2017-02-16 11:00:01')),
            new DomainEvent(new NameChanged('another name'), [], new \DateTimeImmutable('2017-02-16 23:00:00')),
            new DomainEvent(new DescriptionChanged('another name'), [], new \DateTimeImmutable('2017-02-17 11:00:00')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new IdentifiedEventStream('streamId', $storedEvents);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $version = $eventStore->getStreamVersionAt('streamId', new \DateTimeImmutable('2017-02-16 12:00:00'));

        $this->assertEquals(3, $version);
    }

    /**
     * @test
     */
    public function findStreamEventVersionAtDatetimeThatMatchWithEventOccurredOnTime()
    {
        $domainEvents = [
            new DomainEvent(new NameChanged('name'), [], new \DateTimeImmutable('2017-02-15 12:00:00')),
            new DomainEvent(new NameChanged('new name'), [], new \DateTimeImmutable('2017-02-16 11:00:00')),
            new DomainEvent(new DescriptionChanged('new description'), [], new \DateTimeImmutable('2017-02-16 11:00:01')),
            new DomainEvent(new NameChanged('another name'), [], new \DateTimeImmutable('2017-02-16 23:00:00')),
            new DomainEvent(new DescriptionChanged('another name'), [], new \DateTimeImmutable('2017-02-17 11:00:00')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new IdentifiedEventStream('streamId', $storedEvents);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $version = $eventStore->getStreamVersionAt('streamId', new \DateTimeImmutable('2017-02-16 11:00:00'));

        $this->assertEquals(2, $version);
    }

    /**
     * @test
     */
    public function itShouldUpgradeEventsInEventStore()
    {
        $oldStoredEvent = new StoredEvent(
            'id',
            'streamId',
            VersionedEvent::class,
            '{"name":"Name","occurred_on":"2016-12-04 17:35:35"}',
            '{}',
            new \DateTimeImmutable('2016-12-04 17:35:35'),
            Version::fromString('1.0')
        );
        $storedEventStream = new IdentifiedEventStream('streamId', [$oldStoredEvent]);
        $streams = [$storedEventStream->id() => $storedEventStream];
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            $streams
        );

        $eventStore->migrate(
            VersionedEvent::class,
            Version::fromString('1.0'),
            Version::fromString('2.0')
        );

        $stream = $eventStore->readFullStream('streamId');
        $this->assertCount(1, $stream);
        $event = $stream->get(0);
        $this->assertTrue(Version::fromString('2.0')->equalTo($event->version()));
        $this->assertEquals('Name', $event->data()->username());
        $this->assertEquals('2016-12-04 17:35:35', $event->occurredOn()->format('Y-m-d H:i:s'));
    }

    /**
     * @test
     */
    public function addPostAppendEventListener()
    {
        $appendedEvents = [];
        $eventListener = function($events) use (&$appendedEvents) {
            foreach ($events as $event) {
                $appendedEvents[] = $event->data();
            }
        };
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->addEventListener(EventStoreEvents::POST_APPEND, $eventListener);
        $events = [DomainEvent::produceNow(new NameChanged('name'))];

        $eventStore->appendToStream('streamId', $events);

        $this->assertCount(1, $appendedEvents);
        $this->assertInstanceOf(NameChanged::class, $appendedEvents[0]);
        $this->assertEquals('name', $appendedEvents[0]->name());
    }

    /**
     * @test
     */
    public function addPostAppendEventListenerUsingEventStoreListenerInterface()
    {
        $eventListener = new RecorderEventListener();
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->addEventListener(EventStoreEvents::POST_APPEND, $eventListener);
        $events = [DomainEvent::produceNow(new NameChanged('name'))];

        $eventStore->appendToStream('streamId', $events);

        $this->assertCount(1, $eventListener->recordedEvents());
        $this->assertInstanceOf(NameChanged::class, $eventListener->recordedEvents()[0]);
        $this->assertEquals('name', $eventListener->recordedEvents()[0]->name());
    }

    /**
     * @test
     */
    public function addMultiplePostAppendEventListener()
    {
        $anEventListenerCalled = false;
        $anEventListener = function() use (&$anEventListenerCalled) {
            $anEventListenerCalled = true;
        };
        $anotherEventListenerCalled = false;
        $anotherEventListener = function() use (&$anotherEventListenerCalled ) {
            $anotherEventListenerCalled  = true;
        };
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->addEventListener(EventStoreEvents::POST_APPEND, $anEventListener);
        $eventStore->addEventListener(EventStoreEvents::POST_APPEND, $anotherEventListener);
        $events = [DomainEvent::produceNow(new NameChanged('name'))];

        $eventStore->appendToStream('streamId', $events);

        $this->assertTrue($anEventListenerCalled);
        $this->assertTrue($anotherEventListenerCalled);
    }

    /**
     * @test
     */
    public function addPreAppendEventListener()
    {
        $appendedEvents = [];
        $eventListener = function($events) use (&$appendedEvents) {
            foreach ($events as $event) {
                $appendedEvents[] = $event->data();
            }
        };
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader
        );
        $eventStore->addEventListener(EventStoreEvents::PRE_APPEND, $eventListener);
        $events = [DomainEvent::produceNow(new NameChanged('name'))];

        $eventStore->appendToStream('streamId', $events);

        $this->assertCount(1, $appendedEvents);
        $this->assertInstanceOf(NameChanged::class, $appendedEvents[0]);
        $this->assertEquals('name', $appendedEvents[0]->name());
    }

    /**
     * @test
     */
    public function appendEventsWithoutTakeIntoAccountExpectedVersion()
    {
        $domainEvents = [
            DomainEvent::produceNow(new NameChanged('new name')),
            DomainEvent::produceNow(new DescriptionChanged('new description')),
            DomainEvent::produceNow(new NameChanged('another name')),
            DomainEvent::produceNow(new NameChanged('my name')),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new IdentifiedEventStream('streamId', $storedEvents);
        $eventStore = new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );

        $eventStore->appendToStream(
            'streamId',
            [DomainEvent::produceNow(new NameChanged('name'))],
            EventStoreInterface::EXPECTED_VERSION_ANY
        );
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
                $this->serializer->serialize($domainEvent->metadata()),
                $domainEvent->occurredOn(),
                Version::fromString('1.0')
            );
        }, $domainEvents);
    }
}
