<?php

namespace DDDominio\Tests\EventSourcing\TestData;

use DDDominio\EventSourcing\Common\EventSourcedAggregateRepository;

class DummyEventSourcedAggregateRepository extends EventSourcedAggregateRepository
{
    /**
     * @return string
     */
    protected function aggregateClass()
    {
        return 'DummyEventSourcedAggregate';
    }

    /**
     * @param DummyEventSourcedAggregate $aggregate
     * @return string
     */
    protected function aggregateId($aggregate)
    {
        return $aggregate->id();
    }
}
