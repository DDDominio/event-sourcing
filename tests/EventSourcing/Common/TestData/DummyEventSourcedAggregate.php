<?php

namespace DDDominio\Tests\EventSourcing\Common\TestData;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRoot;

class DummyEventSourcedAggregate
{
    use EventSourcedAggregateRoot;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var DummyEntity[]
     */
    private $entityCollection;

    /**
     * @var DummyEntity
     */
    private $entityMember;

    /**
     * @param $id
     * @param string $name
     * @param string $description
     */
    public function __construct($id, $name, $description)
    {
        $this->assertValidName($name);
        $this->apply(new DummyCreated($id, $name, $description, new \DateTimeImmutable()));
    }

    /**
     * @param DummyCreated $event
     */
    private function whenDummyCreated(DummyCreated $event)
    {
        $this->id = $event->id();
        $this->name = $event->name();
        $this->description = $event->description();
        $this->entityCollection = [];
    }

    /**
     * @return string
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
     * @param string $name
     */
    public function changeName($name)
    {
        $this->assertValidName($name);
        $this->apply(new NameChanged($name, new \DateTimeImmutable()));
    }

    /**
     * @param string $name
     */
    private function assertValidName($name)
    {
        if (strlen($name) < 2) {
            throw new \InvalidArgumentException('name should contain at least 2 characters.');
        }
    }

    /**
     * @param NameChanged $event
     */
    private function whenNameChanged(NameChanged $event)
    {
        $this->name = $event->name();
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
        $this->apply(new DescriptionChanged($description, new \DateTimeImmutable()));
    }

    /**
     * @param DescriptionChanged $event
     */
    private function whenDescriptionChanged(DescriptionChanged $event)
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
        $this->apply(new DummyEntityAdded($id, $name, new \DateTimeImmutable()));
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
