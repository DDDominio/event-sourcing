<?php

namespace Tests\EventSourcing\Common\Model\TestData;

use EventSourcing\Common\Model\EventSourcedAggregateRepository;

class DummyEventSourcedAggregateRepository extends EventSourcedAggregateRepository
{
    /**
     * @return string
     */
    protected function aggregateType()
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
