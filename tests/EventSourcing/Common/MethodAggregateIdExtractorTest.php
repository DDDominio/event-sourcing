<?php

namespace DDDominio\Tests\EventSourcing\Common;

use DDDominio\EventSourcing\Common\MethodAggregateIdExtractor;

class MethodAggregateIdExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function extractIdUsingIdPublicMethod()
    {
        $aggregate = new MethodAggregate();
        $aggregateIdExtractor = new MethodAggregateIdExtractor('id');

        $id = $aggregateIdExtractor->extract($aggregate);

        $this->assertEquals('id', $id);
    }

    /**
     * @test
     */
    public function extractIdUsingGetIdPublicMethod()
    {
        $aggregate = new MethodAggregate();
        $aggregateIdExtractor = new MethodAggregateIdExtractor('getId');

        $id = $aggregateIdExtractor->extract($aggregate);

        $this->assertEquals('getId', $id);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\Common\AggregateIdNotFoundException
     */
    public function ifIdCannotBeExtractedAnExceptionIsThrown()
    {
        $aggregate = new MethodAggregate();
        $aggregateIdExtractor = new MethodAggregateIdExtractor('nonExistingPublicMethod');

        $aggregateIdExtractor->extract($aggregate);
    }
}

class MethodAggregate
{
    public function id()
    {
        return 'id';
    }

    public function getId()
    {
        return 'getId';
    }
}
