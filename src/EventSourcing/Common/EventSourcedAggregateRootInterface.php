<?php

namespace DDDominio\EventSourcing\Common;

interface EventSourcedAggregateRootInterface
{
    /**
     * @param mixed $domainEvent
     * @param bool $trackChanges
     * @throws DomainEventNotUnderstandableException
     */
    public function apply($domainEvent, $trackChanges = true);

    /**
     * @return DomainEvent[]
     */
    public function changes();

    /**
     * @return int
     */
    public function version();

    /**
     * @return int
     */
    public function originalVersion();

    public function clearChanges();
}
