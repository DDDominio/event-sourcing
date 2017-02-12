<?php

namespace DDDominio\Tests\EventSourcing\EventStore\Projections;

use DDDominio\EventSourcing\EventStore\InMemoryEventStore;
use DDDominio\EventSourcing\EventStore\Projection\Projector;
use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\EventStore\StoredEventStream;
use DDDominio\EventSourcing\Serialization\SerializerInterface;
use DDDominio\Tests\EventSourcing\TestData\DescriptionChanged;
use DDDominio\Tests\EventSourcing\TestData\NameChanged;
use Doctrine\Common\Annotations\AnnotationRegistry;
use DDDominio\EventSourcing\Common\DomainEvent;
use DDDominio\EventSourcing\EventStore\Projection\ProjectionBuilder;
use DDDominio\EventSourcing\Serialization\JsonSerializer;
use DDDominio\EventSourcing\Versioning\EventAdapter;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use DDDominio\EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use DDDominio\EventSourcing\Versioning\Version;
use JMS\Serializer\SerializerBuilder;

class ProjectionBuilderTest extends \PHPUnit_Framework_TestCase
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
    public function makeASimpleProjectionOfASingleStream()
    {
        $eventStore = $this->makeEventStore([
            'streamId' => [
                new NameChanged('short name'),
                new DescriptionChanged('description'),
                new NameChanged('name with more than 20 characters'),
                new NameChanged('name'),
                new NameChanged('another name with more than 20 characters')
            ]
        ]);

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $projectionBuilder
            ->from('streamId')
            ->when(NameChanged::class, function(NameChanged $event, $state, Projector $projector) {
                if (strlen($event->name()) < 10) {
                    $projector->emit('shortNamesStream', $event);
                }
                if (strlen($event->name()) > 20) {
                    $projector->emit('longNamesStream', $event);
                }
            })
            ->execute();

        $longNamesStream = $eventStore->readFullStream('longNamesStream');
        $this->assertCount(2, $longNamesStream);
        $this->assertEquals('name with more than 20 characters', $longNamesStream->events()[0]->data()->name());
        $this->assertEquals('another name with more than 20 characters', $longNamesStream->events()[1]->data()->name());
        $shortNamesStream = $eventStore->readFullStream('shortNamesStream');
        $this->assertCount(1, $shortNamesStream);
        $this->assertEquals('name', $shortNamesStream->events()[0]->data()->name());
    }

    /**
     * @test
     */
    public function projectionUsingState()
    {
        $eventStore = $this->makeEventStore([
            'streamId' => [
                new NameChanged('short name'),
                new DescriptionChanged('description'),
                new NameChanged('name with more than 20 characters'),
                new NameChanged('name'),
                new NameChanged('another name with more than 20 characters')
            ]
        ]);

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $state = $projectionBuilder
            ->from('streamId')
            ->when(NameChanged::class, function(NameChanged $event, $state) {
                if (strlen($event->name()) < 10) {
                    if (!isset($state->shortNameCount)) {
                        $state->shortNameCount = 0;
                    }
                    $state->shortNameCount++;
                }
            })
            ->execute();

        $this->assertEquals(1, $state->shortNameCount);
    }

    /**
     * @test
     */
    public function initState()
    {
        $eventStore = $this->makeEventStore();

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $state = $projectionBuilder
            ->from('streamId')
            ->init(function ($state) {
                $state->shortNameCount = 0;
                return $state;
            })
            ->execute();

        $this->assertEquals(0, $state->shortNameCount);
    }

    /**
     * @test
     */
    public function initAndUseState()
    {
        $eventStore = $this->makeEventStore([
            'streamId' => [
                new NameChanged('short name'),
                new DescriptionChanged('description'),
                new NameChanged('name with more than 20 characters'),
                new NameChanged('name'),
                new NameChanged('another name with more than 20 characters')
            ]
        ]);

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $state = $projectionBuilder
            ->from('streamId')
            ->init(function ($state) {
                $state->shortNameCount = 0;
                return $state;
            })
            ->when(NameChanged::class, function(NameChanged $event, $state) {
                if (strlen($event->name()) < 10) {
                    $state->shortNameCount++;
                }
            })
            ->execute();

        $this->assertEquals(1, $state->shortNameCount);
    }

    /**
     * @test
     */
    public function projectionFromAllStreams()
    {
        $eventStore = $this->makeEventStore([
            'stream-1' => [
                new NameChanged('medium name'),
                new NameChanged('name with more than 20 characters'),
            ],
            'stream-2' => [
                new NameChanged('name'),
                new NameChanged('medium name'),
                new NameChanged('name with more than 20 characters'),
            ],
            'stream-3' => [
                new NameChanged('name'),
                new NameChanged('name with more than 20 characters'),
            ]
        ]);

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $projectionBuilder
            ->fromAll()
            ->when(NameChanged::class, function(NameChanged $event, $state, Projector $projector) {
                if (strlen($event->name()) < 10) {
                    $projector->emit('shortNamesStream', $event);
                }
            })
            ->execute();

        $shortNamesStream = $eventStore->readFullStream('shortNamesStream');
        $this->assertCount(2, $shortNamesStream);
    }

    /**
     * @test
     */
    public function projectionForEachStream()
    {
        $eventStore = $this->makeEventStore([
            'stream-1' => [
                new NameChanged('name1'),
                new NameChanged('name with more than 20 characters'),
                new NameChanged('name1'),
            ],
            'stream-2' => [
                new NameChanged('name2'),
                new NameChanged('name2'),
                new NameChanged('name with more than 20 characters'),
            ],
            'stream-3' => [
                new NameChanged('name with more than 20 characters'),
                new NameChanged('name3'),
                new NameChanged('name3'),
                new NameChanged('name3'),
            ]
        ]);

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $projectionBuilder
            ->fromAll()
            ->forEachStream()
            ->init(function($state) {
                $state->isPreviousEventShort = false;
            })
            ->when(NameChanged::class, function(NameChanged $event, $state, Projector $projector) {
                if (strlen($event->name()) < 10) {
                    if ($state->isPreviousEventShort) {
                        $projector->emit('twoSortNameInARow', $event);
                    }
                }
                $state->isPreviousEventShort = strlen($event->name()) < 10;
            })
            ->execute();

        $shortNamesStream = $eventStore->readFullStream('twoSortNameInARow');
        $this->assertCount(3, $shortNamesStream);
        $this->assertEquals('name2', $shortNamesStream->events()[0]->data()->name());
        $this->assertEquals('name3', $shortNamesStream->events()[1]->data()->name());
        $this->assertEquals('name3', $shortNamesStream->events()[1]->data()->name());
    }

    /**
     * @param array $streams
     * @return InMemoryEventStore
     */
    private function makeEventStore($streams = [])
    {
        $storedEventStreams = [];
        foreach ($streams as $streamId => $eventsData) {
            $domainEvents = [];
            foreach ($eventsData as $eventData) {
                $domainEvents[] = DomainEvent::record($eventData);
            }
            $storedEventStreams[$streamId] = new StoredEventStream(
                $streamId,
                $this->storedEventsFromDomainEvents($domainEvents)
            );
        }
        return new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            $storedEventStreams
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
