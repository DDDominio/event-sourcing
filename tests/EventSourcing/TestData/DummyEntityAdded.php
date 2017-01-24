<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;

class DummyEntityAdded extends DomainEvent
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
     * @param string $id
     * @param string $name
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($id, $name, \DateTimeImmutable $occurredOn)
    {
        $this->id = $id;
        $this->name = $name;
        parent::__construct([], $occurredOn);
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
}
