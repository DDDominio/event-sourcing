<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\DomainEvent;

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
