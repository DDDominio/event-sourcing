<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\DomainEvent;

class NotUnderstandableDomainEvent extends DomainEvent
{
    /**
     * @param \DateTimeImmutable $occurredOn
     */
    public function __construct(\DateTimeImmutable $occurredOn)
    {
        parent::__construct([], $occurredOn);
    }
}
