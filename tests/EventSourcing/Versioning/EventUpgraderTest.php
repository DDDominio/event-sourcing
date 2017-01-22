<?php

namespace DDDominio\Tests\EventSourcing\Versioning;

use DDDominio\EventSourcing\EventStore\StoredEvent;
use DDDominio\EventSourcing\Versioning\EventAdapter;
use DDDominio\EventSourcing\Versioning\EventUpgrader;
use DDDominio\EventSourcing\Versioning\JsonTransformer\JsonTransformer;
use DDDominio\EventSourcing\Versioning\JsonTransformer\TokenExtractor;
use DDDominio\EventSourcing\Versioning\Version;
use DDDominio\Tests\EventSourcing\TestData\NameChanged;
use DDDominio\Tests\EventSourcing\TestData\NameChangedUpgrade10_20;
use DDDominio\Tests\EventSourcing\TestData\NameChangedUpgrade20_30;

class EventUpgraderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function itShouldUpgradeAnEvent()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            NameChanged::class,
            '{"name":"Name","version":"1.0"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('2.0'));

        $this->assertEquals('{"version":"2.0","username":"Name"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('2.0'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldDowngradeAnEvent()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            NameChanged::class,
            '{"username":"Name","version":"2.0"}',
            new \DateTimeImmutable(),
            Version::fromString('2.0')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('1.0'));

        $this->assertEquals('{"version":"1.0","name":"Name"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('1.0'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldNotUpgradeAnEventIfThereIsNoUpgradeForThatEventType()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Event\Type\Without\Upgrade',
            '{"name":"Name","version":"1.0"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('2.0'));

        $this->assertEquals('{"name":"Name","version":"1.0"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('1.0'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldNotUpgradeAnEventIfThereIsNoUpgradeForThatEventVersion()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            NameChanged::class,
            '{"name":"Name","version":"1.5"}',
            new \DateTimeImmutable(),
            Version::fromString('1.5')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('2.0'));

        $this->assertEquals('{"name":"Name","version":"1.5"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('1.5'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldNotDowngradeAnEventIfThereIsNoUpgradeForThatEventType()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            'Event\Type\Without\Upgrade',
            '{"username":"Name","version":"2.0"}',
            new \DateTimeImmutable(),
            Version::fromString('2.0')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('1.0'));

        $this->assertEquals('{"username":"Name","version":"2.0"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('2.0'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldNotDowngradeAnEventIfThereIsNoUpgradeForThatEventVersion()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            NameChanged::class,
            '{"username":"Name","version":"1.5"}',
            new \DateTimeImmutable(),
            Version::fromString('1.5')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('1.0'));

        $this->assertEquals('{"username":"Name","version":"1.5"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('1.5'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldUpgradeAnEventToLastVersion()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            NameChanged::class,
            '{"name":"Name","version":"1.0"}',
            new \DateTimeImmutable(),
            Version::fromString('1.0')
        );

        $eventUpgrader->migrate($storedEvent);

        $this->assertEquals('{"version":"3.0","name":{"first":"Name","last":""}}', $storedEvent->body());
        $this->assertEquals(Version::fromString('3.0'), $storedEvent->version());
    }

    /**
     * @test
     */
    public function itShouldDowngradeAnEventToFirstVersion()
    {
        $eventUpgrader = $this->buildEventUpgrader();
        $storedEvent = new StoredEvent(
            'id',
            'streamId',
            NameChanged::class,
            '{"version":"3.0","name":{"first":"Name","last":""}}',
            new \DateTimeImmutable(),
            Version::fromString('3.0')
        );

        $eventUpgrader->migrate($storedEvent, Version::fromString('1.0'));

        $this->assertEquals('{"version":"1.0","name":"Name"}', $storedEvent->body());
        $this->assertEquals(Version::fromString('1.0'), $storedEvent->version());
    }

    private function buildEventUpgrader()
    {
        $tokenExtractor = new TokenExtractor();
        $jsonTransformer = new JsonTransformer($tokenExtractor);
        $eventAdapter = new EventAdapter($jsonTransformer);
        $upgrader = new EventUpgrader($eventAdapter);
        $upgrader->registerUpgrade(new NameChangedUpgrade10_20($eventAdapter));
        $upgrader->registerUpgrade(new NameChangedUpgrade20_30($eventAdapter));
        return $upgrader;
    }
}