<?php

namespace Tests\EventSourcing\Common;

use Tests\EventSourcing\Common\TestData\DescriptionChanged;
use Tests\EventSourcing\Common\TestData\DummyCreated;
use Tests\EventSourcing\Common\TestData\DummyEntityNameChanged;
use Tests\EventSourcing\Common\TestData\DummyEventSourcedAggregate;
use Tests\EventSourcing\Common\TestData\NameChanged;
use Tests\EventSourcing\Common\TestData\NotUnderstandableDomainEvent;

class EventSourcedAggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DummyEventSourcedAggregate
     */
    private $eventSourcedAggregate;

    protected function setUp()
    {
        $this->eventSourcedAggregate = (new \ReflectionClass(DummyEventSourcedAggregate::class))
            ->newInstanceWithoutConstructor();;
    }

    /**
     * @test
     */
    public function applyADomainEventWithoutTrackingChanges()
    {
        $nameChangedEvent = new NameChanged('new name', new \DateTimeImmutable());

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
        $nameChangedEvent = new NameChanged('new name', new \DateTimeImmutable());

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
        $oldEvent = new DummyCreated('id', 'a', 'description', new \DateTimeImmutable());

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
        $events[] = new NameChanged('new name', new \DateTimeImmutable());
        $events[] = new NameChanged('new new name', new \DateTimeImmutable());

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
        $descriptionChangedEvent = new DescriptionChanged('new description', new \DateTimeImmutable());

        $this->eventSourcedAggregate->apply($descriptionChangedEvent, false);

        $changes = $this->eventSourcedAggregate->changes();
        $this->assertEquals(0, count($changes));
        $this->assertEquals('new description', $this->eventSourcedAggregate->description());
    }

    /**
     * @test
     * @expectedException \EventSourcing\Common\DomainEventNotUnderstandableException
     */
    public function aggregateDoesNotUnderstandADomainEvent()
    {
        $notUnderstandableDomainEvent = new NotUnderstandableDomainEvent(new \DateTimeImmutable());

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

        $this->eventSourcedAggregate->apply(new NameChanged('new name', new \DateTimeImmutable()));

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
    public function applyConstructorDomainEvent()
    {
        $emptyAggregate = $this->eventSourcedAggregate;
        $dummyCreatedEvent = new DummyCreated('id', 'name', 'description', new \DateTimeImmutable());

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
        $oldDomainEvent = new NameChanged('a', new \DateTimeImmutable());

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

        $aggregate->clearChanges();

        $this->assertCount(0, $aggregate->changes());
    }

    /**
     * @test
     */
    public function applyADomainEventToAnAggregateEntity()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $aggregate->setEntityMember('entityId', 'entityName');
        $entity = $aggregate->entityMember();

        $entity->changeName('new entity name');

        $changes = $aggregate->changes();
        $lastChange = end($changes);
        $this->assertInstanceOf(DummyEntityNameChanged::class, $lastChange);
        $this->assertEquals('new entity name', $lastChange->name());
    }

    /**
     * @test
     */
    public function applyADomainEventToAnAggregateEntityCollection()
    {
        $aggregate = new DummyEventSourcedAggregate('id', 'name', 'description');
        $aggregate->addDummyEntity('entityId', 'entityName');
        $entity = $aggregate->entity('entityId');

        $entity->changeName('new entity name');

        $changes = $aggregate->changes();
        $lastChange = end($changes);
        $this->assertInstanceOf(DummyEntityNameChanged::class, $lastChange);
        $this->assertEquals('new entity name', $lastChange->name());
    }
}
