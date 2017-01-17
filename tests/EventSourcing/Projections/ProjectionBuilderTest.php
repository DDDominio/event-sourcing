<?php

namespace Tests\EventSourcing\Projections;

use Doctrine\Common\Annotations\AnnotationRegistry;
use EventSourcing\Common\DomainEvent;
use EventSourcing\Common\InMemoryEventStore;
use EventSourcing\Common\StoredEvent;
use EventSourcing\Common\StoredEventStream;
use EventSourcing\Projection\ProjectionBuilder;
use EventSourcing\Serialization\JsonSerializer;
use EventSourcing\Serialization\Serializer;
use EventSourcing\Versioning\EventAdapter;
use EventSourcing\Versioning\EventUpgrader;
use EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use EventSourcing\Versioning\Version;
use JMS\Serializer\SerializerBuilder;
use Tests\EventSourcing\Common\TestData\DescriptionChanged;
use Tests\EventSourcing\Common\TestData\NameChanged;

class ProjectionBuilderTest extends \PHPUnit_Framework_TestCase
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
    public function makeASimpleProjection()
    {
        $eventStore = $this->makeEventStore();

        $projectionBuilder = new ProjectionBuilder($eventStore);
        $projectionBuilder
            ->from('streamId')
            ->when(NameChanged::class, function(NameChanged $event) {
                if (strlen($event->name()) > 20) {
                    $this->emit($event);
                }
            })
            ->execute('longNamesStream');

        $projectedStream = $eventStore->readFullStream('longNamesStream');
        $this->assertCount(2, $projectedStream);
        $this->assertEquals('name with more than 20 characters', $projectedStream->events()[0]->name());
        $this->assertEquals('another name with more than 20 characters', $projectedStream->events()[1]->name());
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

    /**
     * @return InMemoryEventStore
     */
    protected function makeEventStore()
    {
        $domainEvents = [
            new NameChanged('short name', new \DateTimeImmutable()),
            new DescriptionChanged('description', new \DateTimeImmutable()),
            new NameChanged('name with more than 20 characters', new \DateTimeImmutable()),
            new NameChanged('name', new \DateTimeImmutable()),
            new NameChanged('another name with more than 20 characters', new \DateTimeImmutable()),
        ];
        $storedEvents = $this->storedEventsFromDomainEvents($domainEvents);
        $storedEventStream = new StoredEventStream('streamId', $storedEvents);
        return new InMemoryEventStore(
            $this->serializer,
            $this->eventUpgrader,
            ['streamId' => $storedEventStream]
        );
    }
}
