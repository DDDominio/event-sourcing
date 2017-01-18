<?php

namespace DDDominio\Tests\EventSourcing\Common\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;

class DummyEntityAdded implements DomainEvent
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
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param string $id
     * @param string $name
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($id, $name, \DateTimeImmutable $occurredOn)
    {
        $this->id = $id;
        $this->name = $name;
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
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
