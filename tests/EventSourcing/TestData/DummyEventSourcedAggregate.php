<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\Annotation\AggregateId;
use DDDominio\EventSourcing\Common\EventSourcedAggregateRoot;

class DummyEventSourcedAggregate extends EventSourcedAggregateRoot
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var DummyEntity[]
     */
    protected $entityCollection;

    /**
     * @var DummyEntity
     */
    protected $entityMember;

    /**
     * @var \DateTimeImmutable
     */
    protected $nameChangedAt;

    /**
     * @param $id
     * @param string $name
     * @param string $description
     */
    public function __construct($id, $name, $description)
    {
        $this->assertValidName($name);
        $this->applyAndRecord(new DummyCreated($id, $name, $description));
    }

    /**
     * @param DummyCreated $event
     */
    protected function whenDummyCreated(DummyCreated $event)
    {
        $this->id = $event->id();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->entityCollection = [];
    }

    /**
     * @return string
     *
     * @AggregateId()
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function nameChangedAt()
    {
        return $this->nameChangedAt;
    }

    /**
     * @param string $name
     */
    public function changeName($name)
    {
        $this->assertValidName($name);
        $this->applyAndRecord(new NameChanged($name));
    }

    /**
     * @param string $name
     */
    protected function assertValidName($name)
    {
        if (strlen($name) < 2) {
            throw new \InvalidArgumentException('name should contain at least 2 characters.');
        }
    }

    /**
     * @param NameChanged $event
     * @param \DateTimeImmutable $occurredOn
     */
    protected function whenNameChanged(NameChanged $event, \DateTimeImmutable $occurredOn)
    {
        $this->name = $event->name();
        $this->nameChangedAt = $occurredOn;
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
        $this->applyAndRecord(new DescriptionChanged($description));
    }

    /**
     * @param DescriptionChanged $event
     */
    protected function whenDescriptionChanged(DescriptionChanged $event)
    {
        $this->description = $event->description();
    }

    /**
     * @return DummyEntity|null
     */
    public function entity($entityId)
    {
        return isset($this->entityCollection[$entityId]) ? $this->entityCollection[$entityId]
            : null;
    }

    /**
     * @param string $id
     * @param string $name
     */
    public function addDummyEntity($id, $name)
    {
        $this->applyAndRecord(new DummyEntityAdded($id, $name));
    }

    /**
     * @param DummyEntityAdded $event
     */
    public function whenDummyEntityAdded(DummyEntityAdded $event)
    {
        $this->entityCollection[$event->id()] = new DummyEntity($event->id(), $this, $event->name());
    }

    /**
     * @return DummyEntity
     */
    public function entityMember()
    {
        return $this->entityMember;
    }

    /**
     * @param string $id
     * @param string $name
     */
    public function setEntityMember($id, $name)
    {
        $this->entityMember = new DummyEntity($id, $this, $name);
    }
}
