<?php

namespace tests\EventSourcing\Versioning;

use EventSourcing\Common\Model\StoredEvent;
use EventSourcing\Versioning\EventAdapter;
use EventSourcing\Versioning\JsonAdapter\JsonAdapter;
use EventSourcing\Versioning\JsonAdapter\TokenExtractor;

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
            new \DateTime()
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
            new \DateTime()
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
            new \DateTime()
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
            new \DateTime()
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
            new \DateTime()
        );

        $eventAdapter->changeValue($storedEvent, 'name', function($body, $value) {
            $value->first = $body->name;
            $value->last = '';
        });

        $this->assertEquals('{"name":{"first":"Name","last":""}}', $storedEvent->body());
    }
}