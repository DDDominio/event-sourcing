<?php

namespace EventSourcing\Common\Model;

interface Event
{
    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn();
}
