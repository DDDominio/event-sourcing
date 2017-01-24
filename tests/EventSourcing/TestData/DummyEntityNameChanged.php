<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;

class DummyEntityNameChanged extends DomainEvent
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct($name, \DateTimeImmutable $occurredOn)
    {
        $this->name = $name;
        parent::__construct([], $occurredOn);
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }
}
