<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\EventSourcedAggregate;

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
        $this->assertValidName($name);
        $this->apply(new DummyCreated($name, $description));
    }

    /**
     * @param DummyCreated $event
     */
    private function whenDummyCreated(DummyCreated $event)
    {
        $this->name = $event->name();
        $this->description = $event->description();
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
        $this->apply(new NameChanged($name));
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
        $this->apply(new DescriptionChanged($description));
    }

    /**
     * @param DescriptionChanged $event
     */
    private function whenDescriptionChanged(DescriptionChanged $event)
    {
        $this->description = $event->description();
    }
}
