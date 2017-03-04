<?php

namespace DDDominio\EventSourcing\Common;

interface EventSourcedAggregateRootInterface
{
    /**
     * @param mixed $domainEvent
     * @throws DomainEventNotUnderstandableException
     */
    public function apply($domainEvent);

    /**
     * @param mixed $domainEvent
     * @throws DomainEventNotUnderstandableException
     */
    public function applyAndRecord($domainEvent);

    /**
     * @return EventStream
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
