<?php

namespace Tests\EventStore\Common\Model\TestData;

use EventStore\Common\Model\EventSourcedAggregate;

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
        $this->changeName($name);
        $this->changeDescription($description);
        $this->publishDomainEvent(new DummyCreated($name, $description));
    }

    public function whenDummyCreated(DummyCreated $event)
    {
        $this->__construct($event->name(), $event->description());
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
