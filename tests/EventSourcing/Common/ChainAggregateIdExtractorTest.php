<?php

namespace DDDominio\Tests\EventSourcing\Common;

use DDDominio\EventSourcing\Common\AggregateIdExtractorInterface;
use DDDominio\EventSourcing\Common\AggregateIdNotFoundException;
use DDDominio\EventSourcing\Common\ChainAggregateIdExtractor;

class ChainAggregateIdExtractorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\Common\AggregateIdNotFoundException
     */
    public function emptyChainCannotExtractAggregateId()
    {
        $aggregateIdExtractor = new ChainAggregateIdExtractor();

        $aggregateIdExtractor->extract(new \stdClass());
    }

    /**
     * @test
     */
    public function addAnUseAnExtractorToTheChain()
    {
        $aggregateIdExtractor = new ChainAggregateIdExtractor();
        $aggregateIdExtractor->add(new FirstExtractor());

        $id = $aggregateIdExtractor->extract(new FirstExtractor());

        $this->assertEquals('first', $id);
    }

    /**
     * @test
     */
    public function whenAddingMultipleExtractorsFirstExtractorHasPriority()
    {
        $aggregateIdExtractor = new ChainAggregateIdExtractor();
        $aggregateIdExtractor->add(new FirstExtractor());
        $aggregateIdExtractor->add(new SecondExtractor());

        $id = $aggregateIdExtractor->extract(new \stdClass());

        $this->assertEquals('first', $id);
    }

    /**
     * @test
     */
    public function ifAnExtractorFailsTheNextExtractorTriesToExtractTheId()
    {
        $aggregateIdExtractor = new ChainAggregateIdExtractor();
        $aggregateIdExtractor->add(new FailingExtractor());
        $aggregateIdExtractor->add(new SecondExtractor());

        $id = $aggregateIdExtractor->extract(new \stdClass());

        $this->assertEquals('second', $id);
    }

    /**
     * @test
     * @expectedException \DDDominio\EventSourcing\Common\AggregateIdNotFoundException
     */
    public function ifAllExtractorsFailsAnExceptionShouldBeThrown()
    {
        $aggregateIdExtractor = new ChainAggregateIdExtractor();
        $aggregateIdExtractor->add(new FailingExtractor());
        $aggregateIdExtractor->add(new FailingExtractor());

        $id = $aggregateIdExtractor->extract(new \stdClass());

        $this->assertEquals('second', $id);
    }
}

class FirstExtractor implements AggregateIdExtractorInterface
{
    public function extract($aggregate)
    {
        return 'first';
    }
}

class FailingExtractor implements AggregateIdExtractorInterface
{
    public function extract($aggregate)
    {
        throw new AggregateIdNotFoundException();
    }
}

class SecondExtractor implements AggregateIdExtractorInterface
{
    public function extract($aggregate)
    {
        return 'second';
    }
}
