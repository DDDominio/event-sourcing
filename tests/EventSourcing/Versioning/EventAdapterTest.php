<?php

namespace tests\EventSourcing\Versioning;

use EventSourcing\Common\Model\StoredEvent;
use EventSourcing\Versioning\EventAdapter;
use EventSourcing\Versioning\JsonAdapter\JsonAdapter;
use EventSourcing\Versioning\JsonAdapter\TokenExtractor;
use EventSourcing\Versioning\Version;

class EventAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldRenameAnStoredEvent()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonAdapter = new JsonAdapter($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonAdapter);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->rename($storedEvent, 'New\Full\Class\Name');

        $this->assertEquals('New\Full\Class\Name', $storedEvent->name());
    }

    /**
     * @test
     */
    public function itShouldRenameAField()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonAdapter = new JsonAdapter($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonAdapter);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->renameField($storedEvent, 'name', 'username');

        $this->assertEquals('{"username":"Name"}', $storedEvent->body());
    }

    /**
     * @test
     */
    public function itShouldEnrichAnEvent()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonAdapter = new JsonAdapter($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonAdapter);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->enrich($storedEvent, 'description', function($body) {
            return 'description_' . $body->name;
        });

        $this->assertEquals('{"name":"Name","description":"description_Name"}', $storedEvent->body());
    }

    /**
     * @test
     */
    public function itShouldRemoveAField()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonAdapter = new JsonAdapter($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonAdapter);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name","description":"Description"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->removeField($storedEvent, 'description');

        $this->assertEquals('{"name":"Name"}', $storedEvent->body());
    }

    /**
     * @test
     */
    public function itShouldChangeAFieldValue()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonAdapter = new JsonAdapter($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonAdapter);
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Full\Class\Name',
            '{"name":"Name"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventAdapter->changeValue($storedEvent, 'name', function($body) {
            $value = json_decode('{}');
            $value->first = $body->name;
            $value->last = '';
            return $value;
        });

        $this->assertEquals('{"name":{"first":"Name","last":""}}', $storedEvent->body());
    }
}