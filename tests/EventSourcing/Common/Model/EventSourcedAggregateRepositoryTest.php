<?php

namespace Tests\EventSourcing\Common\Model;

use EventSourcing\Common\Model\EventSourcedAggregate;
use EventSourcing\Common\Model\DomainEvent;
use EventSourcing\Common\Model\EventSourcedAggregateRepository;
use EventSourcing\Common\Model\EventStore;

class EventSourcedAggregateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function whenSavingAnAggregateChangesShouldBeAppendedToTheEventStream()
    {
        $changes = [
            $this->createMock(DomainEvent::class)
        ];
        $originalVersion = 1;
        $aggregate = $this->getMockBuilder(EventSourcedAggregate::class)
            ->setMethods(['changes', 'originalVersion'])
            ->getMockForTrait();
        $aggregate
            ->expects($this->once())
            ->method('changes')
            ->willReturn($changes);
        $aggregate
            ->expects($this->once())
            ->method('originalVersion')
            ->willReturn($originalVersion);
        $eventStore = $this->getMockBuilder(EventStore::class)
            ->setMethods(['appendToStream', 'readFullStream'])
            ->getMock();
        $eventStore->expects($this->once())
            ->method('appendToStream')
            ->with('streamId', $changes, $originalVersion);
        $repository = new EventSourcedAggregateRepository($eventStore);

        $repository->save($aggregate);
    }

    /**
     * @test
     */
    public function whenSavingAnAggregateChangesMustBeCommitted()
    {
        $aggregate = $this->getMockBuilder(EventSourcedAggregate::class)
            ->setMethods(['commitChanges'])
            ->getMockForTrait();
        $aggregate
            ->expects($this->once())
            ->method('commitChanges');
        $eventStore = $this->createMock(EventStore::class);
        $repository = new EventSourcedAggregateRepository($eventStore);

        $repository->save($aggregate);
    }
}
