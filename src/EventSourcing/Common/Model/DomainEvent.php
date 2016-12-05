<?php

namespace EventSourcing\Common\Model;

interface DomainEvent
{
    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn();
}
