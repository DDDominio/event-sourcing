<?php

namespace Tests\EventSourcing\Common\Model;

use Tests\EventSourcing\Common\Model\TestData\DescriptionChanged;
use Tests\EventSourcing\Common\Model\TestData\DummyCreated;
use Tests\EventSourcing\Common\Model\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\Model\TestData\NameChanged;
use Tests\EventSourcing\Common\Model\TestData\NotUnderstandableDomainEvent;

class EventSourcedAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DummyEventSourcedAggregate
     */
    private $eventSourcedAggregate;

    protected function setUp()
    {
        $this->eventSourcedAggregate = DummyEventSourcedAggregate::buildEmpty();
    }

    /**
     * @test
     */
    public function applyADomainEventWithoutTrackingChanges()
    {
        $nameChangedEvent = new NameChanged('new name');

        $this->eventSourcedAggregate->apply($nameChangedEvent, false);

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(0, count($changes));
        $this->assertEquals('new name', $this->eventSourcedAggregate->name());
    }

    /**
     * @test
     */
    public function applyADomainEventTrackingChanges()
    {
        $nameChangedEvent = new NameChanged('new name');

        $this->eventSourcedAggregate->apply($nameChangedEvent);

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(1, count($changes));
        $this->assertEquals('new name', $this->eventSourcedAggregate->name());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function buildAndInvalidAggregateThrowsAnException()
    {
        new DummyEventSourcedAggregate('id', 'a', 'description');
    }

    /**
     * @test
     */
    public function buildAndInvalidAggregateWithOldEventIsOk()
    {
        $oldEvent = new DummyCreated('id', 'a', 'description');

        $this->eventSourcedAggregate->apply($oldEvent, false);

        $this->assertEquals('a', $oldEvent->name());
    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function buildAndInvalidArgumentThrowsAnException()
    {
        new DummyEventSourcedAggregate('id', 'a', 'description');
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
            $this->eventSourcedAggregate->apply($event, false);
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

        $this->eventSourcedAggregate->apply($descriptionChangedEvent, false);

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(0, count($changes));
        $this->assertEquals('new description', $this->eventSourcedAggregate->description());
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\Model\DomainEventNotUnderstandableException
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

    /**
     * @test
     */
    public function createAnEmptyEventSourcedAggregateObject()
    {
        /** @var DummyEventSourcedAggregate $emptyAggregate */
        $emptyAggregate = DummyEventSourcedAggregate::buildEmpty();

        $this->assertEquals(null, $emptyAggregate->name());
        $this->assertEquals(null, $emptyAggregate->description());
    }

    /**
     * @test
     */
    public function applyConstructorDomainEvent()
    {
        /** @var DummyEventSourcedAggregate $emptyAggregate */
        $emptyAggregate = DummyEventSourcedAggregate::buildEmpty();
        $dummyCreatedEvent = new DummyCreated('id', 'name', 'description');

        $emptyAggregate->apply($dummyCreatedEvent);

        $this->assertEquals('name', $emptyAggregate->name());
        $this->assertEquals('description', $emptyAggregate->description());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function executeACommandThatBreaksCurrentValidationThrowsAnException()
    {
        $this->eventSourcedAggregate->changeName('a');
    }

    /**
     * @test
     */
    public function applyAnOldDomainEventThatBreaksCurrentValidationIsOk()
    {
        $oldDomainEvent = new NameChanged('a');

        $this->eventSourcedAggregate->apply($oldDomainEvent, false);

        $this->assertEquals('a', $this->eventSourcedAggregate->name());
    }

    /**
     * @test
     */
    public function afterApplyAChangeOriginalVersionWillNotChange()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $aggregate->changeName('name');

        $this->assertEquals(0, $aggregate->originalVersion());
    }

    /**
     * @test
     */
    public function commitAggregateChangesWillClearChanges()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $aggregate->changeName('name');

        $aggregate->commitChanges();

        $this->assertCount(0, $aggregate->changes());
    }
}
