<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\DomainEvent;

class DummyCreated implements DomainEvent
{
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
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param string $id
     * @param string $name
     * @param string $description
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($id, $name, $description, \DateTimeImmutable $occurredOn)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->occurredOn = $occurredOn;
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
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
