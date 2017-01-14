<?php

namespace Tests\EventSourcing\Common\TestData;

use EventSourcing\Common\DomainEvent;

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