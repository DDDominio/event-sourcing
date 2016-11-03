<?php

namespace Tests\EventStore\Common\Model;

use EventStore\Common\Model\DomainEvent;
use EventStore\Common\Model\EventSourcedAggregate;

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

class DummyEventSourcedAggregate
{
    use EventSourcedAggregate;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @param string $name
     * @param string $description
     */
    public function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function changeName($name)
    {
        $this->name = $name;
        $this->publishDomainEvent(new NameChanged($name));
    }

    /**
     * @param NameChanged $event
     */
    private function whenNameChanged(NameChanged $event)
    {
        $this->changeName($event->name());
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function changeDescription($description)
    {
        $this->description = $description;
        $this->publishDomainEvent(new DescriptionChanged($description));
    }

    /**
     * @param DescriptionChanged $event
     */
    private function whenDescriptionChanged(DescriptionChanged $event)
    {
        $this->changeDescription($event->description());
    }
}

class NameChanged implements DomainEvent
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }
}

class DescriptionChanged implements DomainEvent
{
    /**
     * @var string
     */
    private $description;

    /**
     * @param string $description
     */
    public function __construct($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function description()
    {
        return $this->description;
    }
}

class NotUnderstandableDomainEvent implements DomainEvent
{
}
