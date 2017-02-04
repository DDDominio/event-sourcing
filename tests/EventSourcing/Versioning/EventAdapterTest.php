<?php

namespace DDDominio\Tests\EventSourcing\Versioning;

use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\Versioning\EventAdapter;
use DDDominio\EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use DDDominio\EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use DDDominio\EventSourcing\Versioning\Version;

class EventAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldRenameAnStoredEvent()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            '{}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->rename($storedEvent, 'New\Full\Class\Name');

        $this->assertEquals('New\Full\Class\Name', $storedEvent->type());
    }

    /**
     * @test
     */
    public function itShouldRenameAField()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            '{}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->renameField($storedEvent, 'name', 'username');

        $this->assertEquals('{"username":"Name"}', $storedEvent->data());
    }

    /**
     * @test
     */
    public function itShouldEnrichAnEvent()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            '{}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->enrich($storedEvent, 'description', function($data) {
            return 'description_' . $data->name;
        });

        $this->assertEquals('{"name":"Name","description":"description_Name"}', $storedEvent->data());
    }

    /**
     * @test
     */
    public function itShouldRemoveAField()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name","description":"Description"}',
            '{}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->removeField($storedEvent, 'description');

        $this->assertEquals('{"name":"Name"}', $storedEvent->data());
    }

    /**
     * @test
     */
    public function itShouldChangeAFieldValue()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            '{}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->changeValue($storedEvent, 'name', function($data) {
            $value = json_decode('{}');
            $value->first = $data->name;
            $value->last = '';
            return $value;
        });

        $this->assertEquals('{"name":{"first":"Name","last":""}}', $storedEvent->data());
    }
}