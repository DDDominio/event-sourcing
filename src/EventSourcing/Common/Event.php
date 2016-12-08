<?php

namespace EventSourcing\Common;

interface Event
{
    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn();
}
