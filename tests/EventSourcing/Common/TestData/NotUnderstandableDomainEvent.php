<?php

namespace DDDominio\Tests\EventSourcing\Common\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;

class NotUnderstandableDomainEvent implements DomainEvent
{
    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct(\DateTimeImmutable $occurredOn)
    {
        $this->occurredOn = $occurredOn;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn()
    {
        return $this->occurredOn;
    }
}
