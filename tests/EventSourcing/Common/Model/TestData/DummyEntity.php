<?php

namespace Tests\EventSourcing\Common\Model\TestData;

class DummyEntity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var DummyEventSourcedAggregate
     */
    private $aggregateRoot;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $id
     * @param DummyEventSourcedAggregate $aggregateRoot
     * @param string $name
     */
    public function __construct($id, DummyEventSourcedAggregate $aggregateRoot, $name)
    {
        $this->id = $id;
        $this->aggregateRoot = $aggregateRoot;
        $this->name = $name;
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
        $this->aggregateRoot->apply(new DummyEntityNameChanged($name, new \DateTimeImmutable()));
    }

    /**
     * @param DummyEntityNameChanged $event
     */
    public function whenDummyEntityNameChanged(DummyEntityNameChanged $event)
    {
        $this->name = $event->name();
    }
}
