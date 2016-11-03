<?php

namespace Tests\EventStore\Common\Model;

use Tests\EventStore\Common\Model\TestData\DescriptionChanged;
use Tests\EventStore\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventStore\Common\Model\TestData\NameChanged;
use Tests\EventStore\Common\Model\TestData\NotUnderstandableDomainEvent;

class EventSourcedAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DummyEventSourcedAggregate
     */
    private $eventSourcedAggregate;

    protected function setUp()
    {
        $this->eventSourcedAggregate = new DummyEventSourcedAggregate('name', 'description');
    }

    /**
     * @test
     */
    public function applyADomainEvent()
    {
        $nameChangedEvent = new NameChanged('new name');

        $this->eventSourcedAggregate->apply($nameChangedEvent);

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(0, count($changes));
        $this->assertEquals('new name', $this->eventSourcedAggregate->name());
    }

    /**
     * @test
     */
    public function applyTwoDomainEvents()
    {
        $events = [];
        $events[] = new NameChanged('new name');
        $events[] = new NameChanged('new new name');

        foreach ($events as $event) {
            $this->eventSourcedAggregate->apply($event);
        }

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(0, count($changes));
        $this->assertEquals('new new name', $this->eventSourcedAggregate->name());
    }

    /**
     * @test
     */
    public function applyDifferentDomainEvent()
    {
        $descriptionChangedEvent = new DescriptionChanged('new description');

        $this->eventSourcedAggregate->apply($descriptionChangedEvent);

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(0, count($changes));
        $this->assertEquals('new description', $this->eventSourcedAggregate->description());
    }

    /**
     * @test
     * @expectedException \EventStore\Common\Model\DomainEventNotUnderstandableException
     */
    public function aggregateDoesNotUnderstandADomainEvent()
    {
        $notUnderstandableDomainEvent = new NotUnderstandableDomainEvent();

        $this->eventSourcedAggregate->apply($notUnderstandableDomainEvent);
    }

    /**
     * @test
     */
    public function aCommandExecutionShouldPublishADomainEvent()
    {
        $this->eventSourcedAggregate->changeName('new name');

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(1, count($changes));
        /** @var NameChanged $change */
        $change = $changes[0];
        $this->assertInstanceOf(NameChanged::class, $change);
        $this->assertEquals('new name', $change->name());
    }

    /**
     * @test
     */
    public function multipleCommandExecutionShouldPublishMultipleEventsInOrder()
    {
        $this->eventSourcedAggregate->changeName('new name');
        $this->eventSourcedAggregate->changeDescription('new description');

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(2, count($changes));
        /** @var NameChanged $firstChange */
        $firstChange = $changes[0];
        $this->assertInstanceOf(NameChanged::class, $firstChange);
        $this->assertEquals('new name', $firstChange->name());
        /** @var DescriptionChanged $secondChange */
        $secondChange = $changes[1];
        $this->assertInstanceOf(DescriptionChanged::class, $secondChange);
        $this->assertEquals('new description', $secondChange->description());
    }

    /**
     * @test
     */
    public function ifNoDomainEventsAppliedVersionShouldBeZero()
    {
        $this->assertEquals(0, $this->eventSourcedAggregate->version());
    }

    /**
     * @test
     */
    public function afterApplyOneDomainEventsVersionShouldIncreaseBeOne()
    {
        $initialVersion = $this->eventSourcedAggregate->version();

        $this->eventSourcedAggregate->apply(new NameChanged('new name'));

        $this->assertEquals($initialVersion + 1, $this->eventSourcedAggregate->version());
    }

    /**
     * @test
     */
    public function afterExecuteACommandThatPublishADomainEventVersionShouldIncreaseByOne()
    {
        $initialVersion = $this->eventSourcedAggregate->version();

        $this->eventSourcedAggregate->changeName('new name');

        $this->assertEquals($initialVersion + 1, $this->eventSourcedAggregate->version());
    }
}
